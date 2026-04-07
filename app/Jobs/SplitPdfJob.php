<?php

namespace App\Jobs;

use App\Models\UserFile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class SplitPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries   = 1;
    public $timeout = 300;

    protected $userFile;
    protected $inputFilePath;
    protected $tempPath;

    public function __construct(UserFile $userFile, string $inputFilePath, string $tempPath)
    {
        $this->userFile      = $userFile;
        $this->inputFilePath = $inputFilePath;
        $this->tempPath      = $tempPath;
    }

    public function handle()
    {
        $workDir = null;

        try {
            if (!file_exists($this->inputFilePath)) {
                throw new \Exception("Input PDF not found: {$this->inputFilePath}");
            }

            $this->userFile->update(["status" => "processing"]);

            $meta   = $this->userFile->metadata ?? [];
            $groups = $meta["groups"] ?? [];

            if (empty($groups)) {
                throw new \Exception("No groups provided.");
            }

            // Working directory for intermediate files
            $workDir = Storage::disk("local")->path("split/" . $this->userFile->ownerId() . "/" . $this->userFile->uuid);
            if (!is_dir($workDir)) {
                mkdir($workDir, 0755, true);
            }

            // Step 1: Extract every unique page referenced across all groups
            $allPages = array_unique(array_merge(...$groups));
            $pageFiles = []; // pageNum => absolute path

            foreach ($allPages as $pageNum) {
                $pageNum  = intval($pageNum);
                $outPath  = "{$workDir}/page_{$pageNum}.pdf";

                $cmd = "gs -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite " .
                       "-dFirstPage={$pageNum} -dLastPage={$pageNum} " .
                       "-sOutputFile=" . escapeshellarg($outPath) . " " .
                       escapeshellarg($this->inputFilePath) . " 2>&1";

                $out = []; $rc = 0;
                exec($cmd, $out, $rc);

                if ($rc !== 0 || !file_exists($outPath)) {
                    throw new \Exception("Failed to extract page {$pageNum}: " . implode("\n", $out));
                }

                $pageFiles[$pageNum] = $outPath;
            }

            // Step 2: Merge pages per group
            $groupFiles = [];
            $pad        = strlen((string) count($groups));

            foreach ($groups as $index => $pages) {
                $label    = str_pad($index + 1, $pad, "0", STR_PAD_LEFT);
                $outPath  = "{$workDir}/group_{$label}.pdf";
                $inputs   = implode(" ", array_map(fn($p) => escapeshellarg($pageFiles[intval($p)]), $pages));

                if (count($pages) === 1) {
                    // Single page — just copy
                    copy($pageFiles[intval($pages[0])], $outPath);
                } else {
                    $cmd = "gs -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite " .
                           "-sOutputFile=" . escapeshellarg($outPath) . " {$inputs} 2>&1";

                    $out = []; $rc = 0;
                    exec($cmd, $out, $rc);

                    if ($rc !== 0 || !file_exists($outPath)) {
                        throw new \Exception("Failed to merge group " . ($index + 1) . ": " . implode("\n", $out));
                    }
                }

                // Friendly filename
                $pageLabel = count($pages) === 1
                    ? "page_" . intval($pages[0])
                    : "pages_" . intval($pages[0]) . "-" . intval(end($pages));

                $groupFiles[$outPath] = "part_{$label}_{$pageLabel}.pdf";
            }

            // Step 3: Package into ZIP
            $zipDir      = Storage::disk("local")->path("split/" . $this->userFile->ownerId());
            $zipFilename = "split-" . date("Ymd-His") . ".zip";
            $zipFullPath = "{$zipDir}/{$zipFilename}";
            $zipRelPath  = "split/" . $this->userFile->ownerId() . "/" . $zipFilename;

            $zip = new ZipArchive();
            if ($zip->open($zipFullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \Exception("Cannot create ZIP.");
            }

            foreach ($groupFiles as $filePath => $zipName) {
                $zip->addFile($filePath, $zipName);
            }
            $zip->close();

            if (!file_exists($zipFullPath)) {
                throw new \Exception("ZIP was not created.");
            }

            $this->userFile->update([
                "status"            => "completed",
                "output_path"       => $zipRelPath,
                "output_size_bytes" => filesize($zipFullPath),
                "processed_at"      => now(),
                "metadata"          => array_merge($meta, [
                    "split_count"      => count($groups),
                    "ghostscript_used" => true,
                ]),
            ]);

        } catch (\Exception $e) {
            $this->userFile->update([
                "status"   => "failed",
                "metadata" => array_merge($this->userFile->metadata ?? [], ["error" => $e->getMessage()]),
            ]);
            Log::error("SplitPdfJob failed: " . $e->getMessage());
            throw $e;
        } finally {
            if ($workDir && is_dir($workDir)) {
                array_map("unlink", glob("{$workDir}/*.pdf"));
                @rmdir($workDir);
            }
            try { Storage::disk("local")->deleteDirectory($this->tempPath); } catch (\Exception $e) {}
        }
    }
}
