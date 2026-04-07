<?php

namespace App\Http\Controllers\Tools;

use App\Http\Controllers\Controller;
use App\Jobs\CompressPdfJob;
use App\Models\UserFile;
use App\Services\Guest\GuestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CompressPdfController extends Controller
{
    use SavesFileForPayment;

    public function __construct(private GuestService $guests) {}

    public function process(Request $request)
    {
        $ctx    = $this->guests->context($request);
        $limits = $ctx->limits;

        $request->validate([
            'files.*' => 'required|file|mimes:pdf|max:' . ($limits['max_file_size_mb'] * 1024),
        ]);

        $file    = $request->file('files')[0];
        $batchId = (string) Str::uuid();

        $tempPath     = $ctx->storagePath('compress', $batchId);
        $inputSize    = $file->getSize();
        $storedPath   = trim($file->store($tempPath, 'local'));
        $absolutePath = Storage::disk('local')->path($storedPath);

        if ($ctx->hasReachedLimit()) {
            return $this->saveForPayment(
                $batchId, $ctx->ownerField(), 'compress_pdf',
                [$file->getClientOriginalName()], $inputSize,
                [$absolutePath], $tempPath,
            );
        }

        $ctx->recordUsage($inputSize, 1);

        $userFile = UserFile::create([
            'uuid'               => $batchId,
            ...$ctx->ownerField(),
            'operation_type'     => 'compress_pdf',
            'original_filenames' => [$file->getClientOriginalName()],
            'input_size_bytes'   => $inputSize,
            'status'             => 'pending',
            'metadata'           => ['batch_id' => $batchId],
        ]);

        CompressPdfJob::dispatch($userFile, $absolutePath, $tempPath);

        return redirect()->route('tools.status', $userFile->uuid);
    }
}
