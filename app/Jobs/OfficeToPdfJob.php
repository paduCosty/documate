<?php

namespace App\Jobs;

use App\Models\UserFile;
use App\Services\Conversion\OfficeToPdfRegistry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class OfficeToPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries   = 1;
    public $timeout = 300;

    public function __construct(
        protected UserFile $userFile,
        protected array    $inputFilePaths,
        protected string   $tempPath,
        protected string   $conversionType,
    ) {}

    public function handle(): void
    {
        $workDir = null;

        try {
            foreach ($this->inputFilePaths as $path) {
                if (!file_exists($path)) {
                    throw new \Exception("Input file not found: {$path}");
                }
            }

            $this->userFile->update(["status" => "processing"]);

            $config  = OfficeToPdfRegistry::get($this->conversionType);
            $workDir = Storage::disk("local")->path(
                "{$config->type}/" . $this->userFile->ownerId() . "/" . $this->userFile->uuid
            );

            if (!is_dir($workDir)) {
                mkdir($workDir, 0755, true);
            }

            $convertedFiles = [];

            foreach ($this->inputFilePaths as $inputPath) {
                $outPath = $this->convert($inputPath, $workDir, $config->converter);
                $convertedFiles[] = $outPath;
            }

            $outDir = Storage::disk("local")->path("{$config->type}/" . $this->userFile->ownerId());

            if (count($convertedFiles) === 1) {
                $outFilename = "{$config->outputPrefix}-" . date("Ymd-His") . ".pdf";
                $outFullPath = "{$outDir}/{$outFilename}";
                $outRelPath  = "{$config->type}/" . $this->userFile->ownerId() . "/" . $outFilename;

                copy($convertedFiles[0], $outFullPath);

                $this->userFile->update([
                    "status"            => "completed",
                    "output_path"       => $outRelPath,
                    "output_size_bytes" => filesize($outFullPath),
                    "processed_at"      => now(),
                    "metadata"          => array_merge($this->userFile->metadata ?? [], [
                        "converted_count" => 1,
                        "converter_used"  => $config->converter,
                    ]),
                ]);
            } else {
                $zipFilename = "{$config->outputPrefix}-" . date("Ymd-His") . ".zip";
                $zipFullPath = "{$outDir}/{$zipFilename}";
                $zipRelPath  = "{$config->type}/" . $this->userFile->ownerId() . "/" . $zipFilename;

                $zip = new ZipArchive();
                if ($zip->open($zipFullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                    throw new \Exception("Cannot create ZIP.");
                }
                foreach ($convertedFiles as $pdfPath) {
                    $zip->addFile($pdfPath, pathinfo($pdfPath, PATHINFO_BASENAME));
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
                        "converted_count" => count($convertedFiles),
                        "converter_used"  => $config->converter,
                    ]),
                ]);
            }

        } catch (\Exception $e) {
            $this->userFile->update([
                "status"   => "failed",
                "metadata" => array_merge($this->userFile->metadata ?? [], ["error" => $e->getMessage()]),
            ]);
            Log::error("OfficeToPdfJob ({$this->conversionType}) failed: " . $e->getMessage());
            throw $e;
        } finally {
            if ($workDir && is_dir($workDir)) {
                array_map("unlink", glob("{$workDir}/*.pdf"));
                @rmdir($workDir);
            }
            try {
                Storage::disk("local")->deleteDirectory($this->tempPath);
            } catch (\Exception $e) {}
        }
    }

    /**
     * Convert a single file to PDF using the specified converter.
     * Add new converters here as new libraries are integrated.
     */
    private function convert(string $inputPath, string $workDir, string $converter): string
    {
        return match ($converter) {
            "libreoffice" => $this->convertWithLibreOffice($inputPath, $workDir),
            default       => throw new \Exception("Unsupported converter: {$converter}"),
        };
    }

    private function convertWithLibreOffice(string $inputPath, string $workDir): string
    {
        $cmd = "HOME=/tmp libreoffice --headless --convert-to pdf "
            . escapeshellarg($inputPath)
            . " --outdir " . escapeshellarg($workDir)
            . " 2>&1";

        $out = []; $rc = 0;
        exec($cmd, $out, $rc);

        $baseName = pathinfo($inputPath, PATHINFO_FILENAME);
        $outPath  = "{$workDir}/{$baseName}.pdf";

        if ($rc !== 0 || !file_exists($outPath)) {
            throw new \Exception(
                "Conversion failed for " . basename($inputPath) . ": " . implode("\n", $out)
            );
        }

        return $outPath;
    }
}
