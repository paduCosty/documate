<?php

namespace App\Http\Controllers\Tools;

use App\Http\Controllers\Controller;
use App\Jobs\PdfToJpgJob;
use App\Models\UserFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PdfToJpgController extends Controller
{
    public function process(Request $request)
    {
        $user       = $request->user();
        $limits     = $user->currentPlanLimits();
        $todayUsage = $user->todayUsage();

        if ($todayUsage->hasReachedLimit($limits["operations_per_day"], $limits["total_bytes_per_day"])) {
            return redirect()->route("pricing")
                ->with("error", "You have reached your daily operation limit. Upgrade to Pro to continue.");
        }

        $request->validate([
            "files.*" => "required|file|mimes:pdf|max:" . ($limits["max_file_size_mb"] * 1024),
        ]);

        $uploadedFiles = $request->file("files", []);
        $file          = is_array($uploadedFiles) ? $uploadedFiles[0] : $uploadedFiles;

        if (!$file) {
            return back()->with("error", "Please upload a PDF file.");
        }

        $batchId  = Str::uuid();
        $tempPath = "temp/pdf-to-jpg/{$user->id}/{$batchId}";

        $inputSize  = $file->getSize();
        $storedPath = $file->store($tempPath, "local");

        if (!is_string($storedPath) || trim($storedPath) === "") {
            throw new \Exception("Failed to store uploaded PDF.");
        }

        $absolutePath = Storage::disk("local")->path(trim($storedPath));

        $todayUsage->recordUsage($inputSize, 1);

        $userFile = UserFile::create([
            "uuid"               => $batchId,
            "user_id"            => $user->id,
            "operation_type"     => "pdf-to-jpg",
            "original_filenames" => [$file->getClientOriginalName()],
            "input_size_bytes"   => $inputSize,
            "status"             => "pending",
            "expires_at"         => now()->addHours(24),
            "metadata"           => [
                "batch_id" => $batchId,
            ],
        ]);

        PdfToJpgJob::dispatch($userFile, $absolutePath, $tempPath);

        return redirect()->route("tools.status", $userFile->uuid);
    }
}
