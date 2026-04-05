<?php

namespace App\Http\Controllers\Tools;

use App\Models\UserFile;
use App\Jobs\MergePdfJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;

class MergePdfController extends Controller
{
    public function process(Request $request)
    {
        $user = $request->user();
        $limits = $user->currentPlanLimits();
        $todayUsage = $user->todayUsage();
        // Debug: check current usage
        // dd($todayUsage->toArray(), $limits);
        if ($todayUsage->hasReachedLimit($limits['operations_per_day'], $limits['total_bytes_per_day'])) {
            return redirect()->route('pricing')
                ->with('error', 'You have reached your daily operation limit. Upgrade to Pro to continue merging PDFs.');
        }
        // dd('sss');
        $request->validate([
            'files.*' => 'required|file|mimes:pdf|max:' . ($limits['max_file_size_mb'] * 1024),
        ]);

        if (count($request->file('files')) < 2) {
            return back()->with('error', 'Please upload at least 2 PDF files to merge.');
        }

        $batchId = Str::uuid();
        $tempPath = "temp/merge/{$user->id}/{$batchId}";

        $originalFilenames = [];
        $totalInputSize = 0;
        $tempFilePaths = [];

        foreach ($request->file('files') as $file) {
            $originalFilenames[] = $file->getClientOriginalName();
            $totalInputSize += $file->getSize();

            // Save file to local disk
            $storedPath = $file->store($tempPath, 'local');

            if (!is_string($storedPath) || trim($storedPath) === '') {
                throw new \Exception("Failed to store uploaded file for batch {$batchId}");
            }

            $storedPath = trim($storedPath);

            // sanitize accidental bloat (ex: returned string includes PHP tag injection)
            if (strpos($storedPath, '<?php') !== false) {
                Log::warning("Stored path malformed, sanitizing path for batch {$batchId}: {$storedPath}");
                $storedPath = preg_replace('/<\?php.*$/s', '', $storedPath);
                $storedPath = trim($storedPath);
            }

            // Convert to absolute path (CRITICAL)
            $absolutePath = Storage::disk('local')->path($storedPath);

            if (!Storage::disk('local')->exists($storedPath) || !file_exists($absolutePath)) {
                Log::error("File not saved correctly (expected path): {$absolutePath} | storedPath: {$storedPath}");
                throw new \Exception("File not saved correctly: {$absolutePath}");
            }

            $tempFilePaths[] = $absolutePath;
        }

        // Mark this merge operation as used for today's quota.
        $todayUsage->recordUsage($totalInputSize, count($originalFilenames));

        $userFile = UserFile::create([
            'uuid'               => $batchId,
            'user_id'            => $user->id,
            'operation_type'     => 'merge_pdf',
            'original_filenames' => $originalFilenames,
            'input_size_bytes'   => $totalInputSize,
            'status'             => 'pending',
            'expires_at'         => now()->addHours(24),
            'metadata'           => [
                'batch_id'   => $batchId,
                'file_count' => count($originalFilenames),
            ],
        ]);

        MergePdfJob::dispatch($userFile, $tempFilePaths, $tempPath);

        return redirect()->route('tools.status', $userFile->uuid);
    }
}
