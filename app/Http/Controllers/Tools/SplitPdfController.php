<?php

namespace App\Http\Controllers\Tools;

use App\Http\Controllers\Controller;
use App\Jobs\SplitPdfJob;
use App\Models\UserFile;
use App\Services\Guest\GuestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SplitPdfController extends Controller
{
    use SavesFileForPayment;

    public function __construct(private GuestService $guests) {}

    public function process(Request $request)
    {
        $ctx    = $this->guests->context($request);
        $limits = $ctx->limits;

        $request->validate([
            'file'   => 'required|file|mimes:pdf|max:' . ($limits['max_file_size_mb'] * 1024),
            'groups' => 'required|string',
        ]);

        $file   = $request->file('file');
        $groups = json_decode($request->input('groups'), true);

        if (!is_array($groups) || empty($groups)) {
            return back()->with('error', 'Invalid page groups.');
        }

        foreach ($groups as $group) {
            if (!is_array($group) || empty($group)) {
                return back()->with('error', 'Each group must contain at least one page.');
            }
        }

        $batchId  = (string) Str::uuid();
        $tempPath = $ctx->storagePath('split', $batchId);

        $inputSize    = $file->getSize();
        $storedPath   = trim($file->store($tempPath, 'local'));
        $absolutePath = Storage::disk('local')->path($storedPath);

        if (!file_exists($absolutePath)) {
            throw new \Exception("File not saved: {$absolutePath}");
        }

        if ($ctx->hasReachedLimit()) {
            return $this->saveForPayment(
                $batchId, $ctx->ownerField(), 'split_pdf',
                [$file->getClientOriginalName()], $inputSize,
                [$absolutePath], $tempPath,
                ['groups' => $groups, 'group_count' => count($groups)],
            );
        }

        $ctx->recordUsage($inputSize, 1);

        $userFile = UserFile::create([
            'uuid'               => $batchId,
            ...$ctx->ownerField(),
            'operation_type'     => 'split_pdf',
            'original_filenames' => [$file->getClientOriginalName()],
            'input_size_bytes'   => $inputSize,
            'status'             => 'pending',
            'metadata'           => ['batch_id' => $batchId, 'groups' => $groups, 'group_count' => count($groups)],
        ]);

        SplitPdfJob::dispatch($userFile, $absolutePath, $tempPath);

        return redirect()->route('tools.status', $userFile->uuid);
    }
}
