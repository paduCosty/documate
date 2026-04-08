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
use ZipArchive;

class PdfToJpgJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries   = 1;
    public $timeout = 300;

    public function __construct(
        protected UserFile $userFile,
        protected string   $inputFilePath,
        protected string   $tempPath,
        protected int      $dpi = 150,
        protected int      $quality = 85,
    ) {}

    public function handle(): void
    {
        $workDir = null;

        try {
            if (!file_exists($this->inputFilePath)) {
                throw new \Exception("Input PDF not found: {$this->inputFilePath}");
            }

            $this->userFile->update(["status" => "processing"]);

            $workDir = Storage::disk("local")->path(
                "pdf-to-jpg/" . $this->userFile->ownerId() . "/" . $this->userFile->uuid
            );

            if (!is_dir($workDir)) {
                mkdir($workDir, 0755, true);
            }

            // Convert all pages to JPG using pdftoppm
            $prefix = escapeshellarg("{$workDir}/page");
            $cmd    = "pdftoppm -jpeg -r {$this->dpi} -jpegopt quality={$this->quality} "
                    . escapeshellarg($this->inputFilePath)
                    . " {$prefix} 2>&1";

            $out = []; $rc = 0;
            exec($cmd, $out, $rc);

            if ($rc !== 0) {
                throw new \Exception("pdftoppm failed: " . implode("\n", $out));
            }

            $jpgFiles = glob("{$workDir}/page-*.jpg") ?: glob("{$workDir}/page-*.jpeg") ?: [];
            sort($jpgFiles);

            if (empty($jpgFiles)) {
                throw new \Exception("No images were produced. Output: " . implode("\n", $out));
            }

            $outDir      = Storage::disk("local")->path("pdf-to-jpg/" . $this->userFile->ownerId());
            $zipFilename = "pdf-to-jpg-" . date("Ymd-His") . ".zip";
            $zipFullPath = "{$outDir}/{$zipFilename}";
            $zipRelPath  = "pdf-to-jpg/" . $this->userFile->ownerId() . "/" . $zipFilename;

            $zip = new ZipArchive();
            if ($zip->open($zipFullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \Exception("Cannot create ZIP.");
            }

            foreach ($jpgFiles as $index => $jpgPath) {
                $pageNum = str_pad($index + 1, 3, "0", STR_PAD_LEFT);
                $zip->addFile($jpgPath, "page-{$pageNum}.jpg");
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
                "metadata"          => array_merge($this->userFile->metadata ?? [], [
                    "page_count" => count($jpgFiles),
                    "dpi"        => $this->dpi,
                ]),
            ]);

        } catch (\Exception $e) {
            $this->userFile->update([
                "status"   => "failed",
                "metadata" => array_merge($this->userFile->metadata ?? [], ["error" => $e->getMessage()]),
            ]);
            Log::error("PdfToJpgJob failed: " . $e->getMessage());
            throw $e;
        } finally {
            if ($workDir && is_dir($workDir)) {
                array_map("unlink", glob("{$workDir}/*.jpg"));
                array_map("unlink", glob("{$workDir}/*.jpeg"));
                @rmdir($workDir);
            }
            try {
                Storage::disk("local")->deleteDirectory($this->tempPath);
            } catch (\Exception $e) {}
        }
    }
}
