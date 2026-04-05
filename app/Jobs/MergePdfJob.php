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

class MergePdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    protected $userFile;
    protected $tempFilePaths;
    protected $tempPath;

    public function __construct(UserFile $userFile, array $tempFilePaths, string $tempPath)
    {
        $this->userFile = $userFile;
        $this->tempFilePaths = $tempFilePaths;
        $this->tempPath = $tempPath;
    }

    public function handle()
    {
        try {
            $this->userFile->update(['status' => 'processing']);

            // Validate all input files
            foreach ($this->tempFilePaths as $filePath) {
                if (!file_exists($filePath)) {
                    throw new \Exception("Temporary PDF file not found: {$filePath}");
                }

                if (!is_readable($filePath)) {
                    throw new \Exception("File not readable: {$filePath}");
                }
            }

            $outputFilename = 'merged-' . Str::uuid() . '.pdf';
            $outputRelativePath = "merged/{$this->userFile->user_id}/{$outputFilename}";
            $outputFullPath = Storage::disk('local')->path($outputRelativePath);

            if (!is_dir(dirname($outputFullPath))) {
                mkdir(dirname($outputFullPath), 0755, true);
            }

            $inputFiles = implode(' ', array_map('escapeshellarg', $this->tempFilePaths));

            // Capture stderr for debugging
            $command = "gs -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite " .
                       "-sOutputFile=" . escapeshellarg($outputFullPath) . " " .
                       $inputFiles . " 2>&1";

            Log::info("Running GS: " . $command);

            $output = [];
            $returnCode = 0;

            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new \Exception("Ghostscript failed: " . implode("\n", $output));
            }

            if (!file_exists($outputFullPath)) {
                throw new \Exception("Merged file not created.");
            }

            $this->userFile->update([
                'status'            => 'completed',
                'output_path'       => $outputRelativePath,
                'output_size_bytes' => filesize($outputFullPath),
                'processed_at'      => now(),
                'metadata'          => array_merge($this->userFile->metadata ?? [], [
                    'merged_file_count' => count($this->tempFilePaths),
                    'ghostscript_used'  => true,
                ]),
            ]);

        } catch (\Exception $e) {

            $this->userFile->update([
                'status' => 'failed',
                'metadata' => array_merge($this->userFile->metadata ?? [], [
                    'error' => $e->getMessage(),
                ]),
            ]);

            Log::error("MergePdfJob failed: " . $e->getMessage());

            throw $e;

        } finally {
            // Always cleanup temp files
            $this->cleanupTempFiles();
        }
    }

    private function cleanupTempFiles(): void
    {
        try {
            Storage::disk('local')->deleteDirectory($this->tempPath);
        } catch (\Exception $e) {
            Log::warning("Cleanup failed: {$this->tempPath}");
        }
    }
}