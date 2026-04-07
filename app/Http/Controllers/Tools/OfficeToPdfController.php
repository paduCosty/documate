<?php

namespace App\Http\Controllers\Tools;

use App\Http\Controllers\Controller;
use App\Jobs\OfficeToPdfJob;
use App\Models\UserFile;
use App\Services\Conversion\OfficeToPdfRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OfficeToPdfController extends Controller
{
    public function process(Request $request, string $conversionType)
    {
        $config = OfficeToPdfRegistry::get($conversionType);

        $user       = $request->user();
        $limits     = $user->currentPlanLimits();
        $todayUsage = $user->todayUsage();

        if ($todayUsage->hasReachedLimit($limits["operations_per_day"], $limits["total_bytes_per_day"])) {
            return redirect()->route("pricing")
                ->with("error", "You have reached your daily operation limit. Upgrade to Pro to continue.");
        }

        $mimeList = implode(",", $config->mimes);
        $request->validate([
            "files.*" => "required|file|mimes:{$mimeList}|max:" . ($limits["max_file_size_mb"] * 1024),
        ]);

        $uploadedFiles = $request->file("files", []);

        if (empty($uploadedFiles)) {
            return back()->with("error", "Please upload at least one file.");
        }

        $batchId  = Str::uuid();
        $tempPath = "temp/{$config->type}/{$user->id}/{$batchId}";

        $storedFiles    = [];
        $totalInputSize = 0;
        $originalNames  = [];

        foreach ($uploadedFiles as $file) {
            $originalNames[]  = $file->getClientOriginalName();
            $totalInputSize  += $file->getSize();
            $storedPath       = $file->store($tempPath, "local");
            $storedFiles[]    = Storage::disk("local")->path($storedPath);
        }

        $todayUsage->recordUsage($totalInputSize, 1);

        $userFile = UserFile::create([
            "uuid"               => $batchId,
            "user_id"            => $user->id,
            "operation_type"     => $config->operationType,
            "original_filenames" => $originalNames,
            "input_size_bytes"   => $totalInputSize,
            "status"             => "pending",
            "expires_at"         => now()->addHours(24),
            "metadata"           => [
                "batch_id"        => $batchId,
                "file_count"      => count($uploadedFiles),
                "conversion_type" => $config->type,
            ],
        ]);

        OfficeToPdfJob::dispatch($userFile, $storedFiles, $tempPath, $config->type);

        return redirect()->route("tools.status", $userFile->uuid);
    }
}
