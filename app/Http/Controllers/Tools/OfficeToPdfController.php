<?php

namespace App\Http\Controllers\Tools;

use App\Http\Controllers\Controller;
use App\Jobs\OfficeToPdfJob;
use App\Models\UserFile;
use App\Services\Conversion\OfficeToPdfRegistry;
use App\Services\Guest\GuestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OfficeToPdfController extends Controller
{
    use SavesFileForPayment;

    public function __construct(private GuestService $guests) {}

    public function process(Request $request, string $conversionType)
    {
        $config = OfficeToPdfRegistry::get($conversionType);

        $ctx    = $this->guests->context($request);
        $limits = $ctx->limits;

        $maxKb = $limits['max_file_size_mb'] * 1024;
        $request->validate([
            'files'   => 'required|array|min:1|max:' . $config->maxFiles,
            'files.*' => 'required|file|mimes:' . implode(',', $config->mimes) . '|max:' . $maxKb,
        ]);

        $uploadedFiles     = $request->file('files');
        $originalFilenames = [];
        $totalInputSize    = 0;
        $batchId           = (string) Str::uuid();
        $tempPath          = $ctx->storagePath(str_replace('-', '_', $conversionType), $batchId);
        $storedPaths       = [];

        foreach ($uploadedFiles as $file) {
            $originalFilenames[] = $file->getClientOriginalName();
            $totalInputSize     += $file->getSize();
            $storedPath          = trim($file->store($tempPath, 'local'));
            $storedPaths[]       = Storage::disk('local')->path($storedPath);
        }

        if ($ctx->hasReachedLimit()) {
            return $this->saveForPayment(
                $batchId, $ctx->ownerField(), $config->operationType,
                $originalFilenames, $totalInputSize,
                $storedPaths, $tempPath,
            );
        }

        $ctx->recordUsage($totalInputSize, 1);

        $userFile = UserFile::create([
            'uuid'               => $batchId,
            ...$ctx->ownerField(),
            'operation_type'     => $config->operationType,
            'original_filenames' => $originalFilenames,
            'input_size_bytes'   => $totalInputSize,
            'status'             => 'pending',
            'metadata'           => ['batch_id' => $batchId, 'conversion_type' => $conversionType],
        ]);

        OfficeToPdfJob::dispatch($userFile, $storedPaths, $tempPath, $conversionType);

        return redirect()->route('tools.status', $userFile->uuid);
    }
}
