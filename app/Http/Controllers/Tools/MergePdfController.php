<?php

namespace App\Http\Controllers\Tools;

use App\Http\Controllers\Controller;
use App\Jobs\MergePdfJob;
use App\Models\UserFile;
use App\Services\Guest\GuestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MergePdfController extends Controller
{
    use SavesFileForPayment;

    public function __construct(private GuestService $guests) {}

    public function process(Request $request)
    {
        $ctx    = $this->guests->context($request);
        $limits = $ctx->limits;

        $request->validate([
            'files'   => 'required|array|min:2',
            'files.*' => 'required|file|mimes:pdf|max:' . ($limits['max_file_size_mb'] * 1024),
        ]);

        if (count($request->file('files')) < 2) {
            return back()->with('error', 'Please upload at least 2 PDF files to merge.');
        }

        $batchId  = (string) Str::uuid();
        $tempPath = $ctx->storagePath('merge', $batchId);

        $originalFilenames = [];
        $totalInputSize    = 0;
        $tempFilePaths     = [];

        foreach ($request->file('files') as $file) {
            $originalFilenames[] = $file->getClientOriginalName();
            $totalInputSize     += $file->getSize();

            $storedPath   = trim($file->store($tempPath, 'local'));
            $absolutePath = Storage::disk('local')->path($storedPath);

            if (!file_exists($absolutePath)) {
                throw new \Exception("File not saved: {$absolutePath}");
            }

            $tempFilePaths[] = $absolutePath;
        }

        if ($ctx->hasReachedLimit()) {
            return $this->saveForPayment(
                $batchId, $ctx->ownerField(), 'merge_pdf',
                $originalFilenames, $totalInputSize,
                $tempFilePaths, $tempPath,
            );
        }

        $ctx->recordUsage($totalInputSize, 1);

        $userFile = UserFile::create([
            'uuid'               => $batchId,
            ...$ctx->ownerField(),
            'operation_type'     => 'merge_pdf',
            'original_filenames' => $originalFilenames,
            'input_size_bytes'   => $totalInputSize,
            'status'             => 'pending',
            'metadata'           => ['batch_id' => $batchId, 'file_count' => count($originalFilenames)],
        ]);

        MergePdfJob::dispatch($userFile, $tempFilePaths, $tempPath);

        return redirect()->route('tools.status', $userFile->uuid);
    }
}
