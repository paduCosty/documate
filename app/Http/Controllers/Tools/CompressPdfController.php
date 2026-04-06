<?php

namespace App\Http\Controllers\Tools;

use App\Http\Controllers\Controller;
use App\Jobs\CompressPdfJob;
use App\Models\UserFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CompressPdfController extends Controller
{
    public function process(Request $request)
    {
        $user = $request->user();
        $limits = $user->currentPlanLimits();
        $todayUsage = $user->todayUsage();

        if ($todayUsage->hasReachedLimit($limits["operations_per_day"], $limits["total_bytes_per_day"])) {
            return redirect()->route("pricing")
                ->with("error", "You have reached your daily operation limit. Upgrade to Pro to continue.");
        }

        $request->validate([
            "files.*" => "required|file|mimes:pdf|max:" . ($limits["max_file_size_mb"] * 1024),
        ]);

        $file = $request->file("files")[0];

        $batchId = Str::uuid();
        $tempPath = "temp/compress/{$user->id}/{$batchId}";

        $totalInputSize = $file->getSize();
        $storedPath = $file->store($tempPath, "local");
        $absolutePath = Storage::disk("local")->path($storedPath);

        $todayUsage->recordUsage($totalInputSize, 1);

        $userFile = UserFile::create([
            "uuid"               => $batchId,
            "user_id"            => $user->id,
            "operation_type"     => "compress_pdf",
            "original_filenames" => [$file->getClientOriginalName()],
            "input_size_bytes"   => $totalInputSize,
            "status"             => "pending",
            "expires_at"         => now()->addHours(24),
            "metadata"           => [
                "batch_id"   => $batchId,
                "file_count" => 1,
            ],
        ]);

        CompressPdfJob::dispatch($userFile, $absolutePath, $tempPath);

        return redirect()->route("tools.status", $userFile->uuid);
    }
}
