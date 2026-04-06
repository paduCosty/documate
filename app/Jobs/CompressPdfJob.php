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

class CompressPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    protected $userFile;
    protected $inputFilePath;
    protected $tempPath;

    public function __construct(UserFile $userFile, string $inputFilePath, string $tempPath)
    {
        $this->userFile = $userFile;
        $this->inputFilePath = $inputFilePath;
        $this->tempPath = $tempPath;
    }

    public function handle()
    {
        try {
            $this->userFile->update(["status" => "processing"]);

            if (!file_exists($this->inputFilePath)) {
                throw new \Exception("Input PDF not found: {$this->inputFilePath}");
            }

            $outputFilename = "compressed-" . date("Ymd-His") . ".pdf";
            $outputRelativePath = "compressed/{$this->userFile->user_id}/{$outputFilename}";
            $outputFullPath = Storage::disk("local")->path($outputRelativePath);

            if (!is_dir(dirname($outputFullPath))) {
                mkdir(dirname($outputFullPath), 0755, true);
            }

            $command = "gs -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite " .
                       "-dCompatibilityLevel=1.4 -dPDFSETTINGS=/ebook " .
                       "-sOutputFile=" . escapeshellarg($outputFullPath) . " " .
                       escapeshellarg($this->inputFilePath) . " 2>&1";

            Log::info("Running GS compress: " . $command);

            $output = [];
            $returnCode = 0;

            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new \Exception("Ghostscript failed: " . implode("\n", $output));
            }

            if (!file_exists($outputFullPath)) {
                throw new \Exception("Compressed file not created.");
            }

            $outputSize = filesize($outputFullPath);
            $inputSize = $this->userFile->input_size_bytes;
            $savedBytes = max(0, $inputSize - $outputSize);
            $savedPercent = $inputSize > 0 ? round(($savedBytes / $inputSize) * 100) : 0;

            $this->userFile->update([
                "status"            => "completed",
                "output_path"       => $outputRelativePath,
                "output_size_bytes" => $outputSize,
                "processed_at"      => now(),
                "metadata"          => array_merge($this->userFile->metadata ?? [], [
                    "ghostscript_used" => true,
                    "saved_bytes"      => $savedBytes,
                    "saved_percent"    => $savedPercent,
                    "settings"         => "ebook",
                ]),
            ]);

        } catch (\Exception $e) {
            $this->userFile->update([
                "status" => "failed",
                "metadata" => array_merge($this->userFile->metadata ?? [], [
                    "error" => $e->getMessage(),
                ]),
            ]);

            Log::error("CompressPdfJob failed: " . $e->getMessage());

            throw $e;
        } finally {
            $this->cleanupTempFiles();
        }
    }

    private function cleanupTempFiles(): void
    {
        try {
            Storage::disk("local")->deleteDirectory($this->tempPath);
        } catch (\Exception $e) {
            Log::warning("Cleanup failed: {$this->tempPath}");
        }
    }
}
