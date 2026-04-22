<?php

namespace App\Console\Commands;

use App\Models\ExtractionJob;
use Illuminate\Console\Command;

/**
 * Deletes expired ExtractionJob records and their output files from disk.
 * Scheduled daily to prevent unbounded growth of storage/app/extractions/.
 */
class CleanupExtractionJobs extends Command
{
    protected $signature   = 'extraction:cleanup';
    protected $description = 'Delete expired extraction jobs and their output files.';

    public function handle(): int
    {
        $expired = ExtractionJob::expired()->get();

        $deletedJobs  = 0;
        $deletedFiles = 0;

        foreach ($expired as $job) {
            // Delete the output file from disk if it still exists.
            if ($job->output_path && file_exists($job->output_path)) {
                @unlink($job->output_path);
                $deletedFiles++;

                // Remove the job-specific directory if it is now empty.
                $dir = dirname($job->output_path);
                if (is_dir($dir) && count(scandir($dir)) === 2) {
                    @rmdir($dir);
                }
            }

            $job->delete();
            $deletedJobs++;
        }

        $this->info("Extraction cleanup complete: {$deletedJobs} job(s) deleted, {$deletedFiles} file(s) removed.");

        return self::SUCCESS;
    }
}
