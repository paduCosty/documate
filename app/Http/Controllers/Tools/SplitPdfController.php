<?php

namespace App\Http\Controllers\Tools;

use App\Http\Controllers\Controller;
use App\Jobs\SplitPdfJob;
use App\Models\UserFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SplitPdfController extends Controller
{
    public function process(Request $request)
    {
        $user       = $request->user();
        $limits     = $user->currentPlanLimits();
        $todayUsage = $user->todayUsage();

        if ($todayUsage->hasReachedLimit($limits["operations_per_day"], $limits["total_bytes_per_day"])) {
            return redirect()->route("pricing")
                ->with("error", "You have reached your daily operation limit.");
        }

        $request->validate([
            "file"   => "required|file|mimes:pdf|max:" . ($limits["max_file_size_mb"] * 1024),
            "groups"  => "required|string",
        ]);

        $file   = $request->file("file");
        $groups = json_decode($request->input("groups"), true);

        if (!is_array($groups) || empty($groups)) {
            return back()->with("error", "Invalid page groups.");
        }

        // Validate group content — each must be a non-empty array of positive ints
        foreach ($groups as $group) {
            if (!is_array($group) || empty($group)) {
                return back()->with("error", "Each group must contain at least one page.");
            }
        }

        $batchId  = Str::uuid();
        $tempPath = "temp/split/{$user->id}/{$batchId}";

        $inputSize  = $file->getSize();
        $storedPath = $file->store($tempPath, "local");

        if (!is_string($storedPath) || trim($storedPath) === "") {
            throw new \Exception("Failed to store uploaded PDF.");
        }
        $storedPath   = trim($storedPath);
        $absolutePath = Storage::disk("local")->path($storedPath);

        if (!Storage::disk("local")->exists($storedPath) || !file_exists($absolutePath)) {
            throw new \Exception("File not saved correctly: {$absolutePath}");
        }

        $todayUsage->recordUsage($inputSize, 1);

        $userFile = UserFile::create([
            "uuid"               => $batchId,
            "user_id"            => $user->id,
            "operation_type"     => "split_pdf",
            "original_filenames" => [$file->getClientOriginalName()],
            "input_size_bytes"   => $inputSize,
            "status"             => "pending",
            "expires_at"         => now()->addHours(24),
            "metadata"           => [
                "batch_id"   => $batchId,
                "groups"     => $groups,
                "group_count" => count($groups),
            ],
        ]);

        SplitPdfJob::dispatch($userFile, $absolutePath, $tempPath);

        return redirect()->route("tools.status", $userFile->uuid);
    }
}
