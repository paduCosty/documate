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

class WordToPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries   = 1;
    public $timeout = 300;

    protected UserFile $userFile;
    protected array    $inputFilePaths;
    protected string   $tempPath;

    public function __construct(UserFile $userFile, array $inputFilePaths, string $tempPath)
    {
        $this->userFile       = $userFile;
        $this->inputFilePaths = $inputFilePaths;
        $this->tempPath       = $tempPath;
    }

    public function handle()
    {
        $workDir = null;

        try {
            foreach ($this->inputFilePaths as $path) {
                if (!file_exists($path)) {
                    throw new \Exception("Input file not found: {$path}");
                }
            }

            $this->userFile->update(['status' => 'processing']);

            $workDir = Storage::disk('local')->path("word-to-pdf/{$this->userFile->user_id}/{$this->userFile->uuid}");
            if (!is_dir($workDir)) {
                mkdir($workDir, 0755, true);
            }

            $convertedFiles = [];

            foreach ($this->inputFilePaths as $inputPath) {
                // LibreOffice converts to PDF in the workDir
                $cmd = 'HOME=/tmp libreoffice --headless --convert-to pdf '
                    . escapeshellarg($inputPath)
                    . ' --outdir ' . escapeshellarg($workDir)
                    . ' 2>&1';

                $out = []; $rc = 0;
                exec($cmd, $out, $rc);

                // LibreOffice names the output file as <inputbasename>.pdf
                $baseName  = pathinfo($inputPath, PATHINFO_FILENAME);
                $outPath   = "{$workDir}/{$baseName}.pdf";

                if ($rc !== 0 || !file_exists($outPath)) {
                    throw new \Exception('Conversion failed for ' . basename($inputPath) . ': ' . implode("\n", $out));
                }

                $convertedFiles[] = $outPath;
            }

            // Single file → return PDF directly; multiple → ZIP
            if (count($convertedFiles) === 1) {
                $outFilename    = 'word-to-pdf-' . date('Ymd-His') . '.pdf';
                $outDir         = Storage::disk('local')->path("word-to-pdf/{$this->userFile->user_id}");
                $outFullPath    = "{$outDir}/{$outFilename}";
                $outRelPath     = "word-to-pdf/{$this->userFile->user_id}/{$outFilename}";

                copy($convertedFiles[0], $outFullPath);

                $this->userFile->update([
                    'status'            => 'completed',
                    'output_path'       => $outRelPath,
                    'output_size_bytes' => filesize($outFullPath),
                    'processed_at'      => now(),
                    'metadata'          => array_merge($this->userFile->metadata ?? [], [
                        'converted_count' => 1,
                        'libreoffice_used' => true,
                    ]),
                ]);
            } else {
                $zipFilename = 'word-to-pdf-' . date('Ymd-His') . '.zip';
                $zipDir      = Storage::disk('local')->path("word-to-pdf/{$this->userFile->user_id}");
                $zipFullPath = "{$zipDir}/{$zipFilename}";
                $zipRelPath  = "word-to-pdf/{$this->userFile->user_id}/{$zipFilename}";

                $zip = new ZipArchive();
                if ($zip->open($zipFullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                    throw new \Exception('Cannot create ZIP.');
                }
                foreach ($convertedFiles as $pdfPath) {
                    $zip->addFile($pdfPath, pathinfo($pdfPath, PATHINFO_BASENAME));
                }
                $zip->close();

                if (!file_exists($zipFullPath)) {
                    throw new \Exception('ZIP was not created.');
                }

                $this->userFile->update([
                    'status'            => 'completed',
                    'output_path'       => $zipRelPath,
                    'output_size_bytes' => filesize($zipFullPath),
                    'processed_at'      => now(),
                    'metadata'          => array_merge($this->userFile->metadata ?? [], [
                        'converted_count'  => count($convertedFiles),
                        'libreoffice_used' => true,
                    ]),
                ]);
            }

        } catch (\Exception $e) {
            $this->userFile->update([
                'status'   => 'failed',
                'metadata' => array_merge($this->userFile->metadata ?? [], ['error' => $e->getMessage()]),
            ]);
            Log::error('WordToPdfJob failed: ' . $e->getMessage());
            throw $e;
        } finally {
            if ($workDir && is_dir($workDir)) {
                array_map('unlink', glob("{$workDir}/*.pdf"));
                @rmdir($workDir);
            }
            try { Storage::disk('local')->deleteDirectory($this->tempPath); } catch (\Exception $e) {}
        }
    }
}
