<?php

namespace App\Http\Controllers;

use App\Models\UserFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class UserFileController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'file', 'mimes:pdf', 'max:51200'], // 50 MB each
            'operation_type' => ['nullable', 'string', 'max:50'],
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $operationType = $request->input('operation_type', 'unknown');
        $uploadBatchUid = now()->format('YmdHis') . '-' . uniqid();
        $uploadBasePath = "user_files/{$user->id}/{$uploadBatchUid}";

        $storedFiles = [];
        $originalFilenames = [];
        $inputSizeBytes = 0;

        foreach ($request->file('files', []) as $uploadedFile) {
            $originalFilenames[] = $uploadedFile->getClientOriginalName();
            $inputSizeBytes += $uploadedFile->getSize() ?: 0;

            $path = $uploadedFile->storeAs($uploadBasePath, $uploadedFile->getClientOriginalName(), 'local');

            $storedFiles[] = [
                'path' => $path,
                'size' => $uploadedFile->getSize(),
            ];
        }

        $userFile = UserFile::create([
            'user_id' => $user->id,
            'operation_type' => $operationType,
            'original_filenames' => $originalFilenames,
            'input_size_bytes' => $inputSizeBytes,
            'output_size_bytes' => $inputSizeBytes,
            'output_path' => $uploadBasePath,
            'status' => 'completed',
            'processed_at' => now(),
            'metadata' => [
                'uploaded_files' => $storedFiles,
            ],
        ]);
        return response()->json([
            'message' => 'Files uploaded and saved successfully',
            'data' => $userFile,
            'output_path' => $uploadBasePath,
        ], 201);
    }

    public function download(Request $request, $fileId)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $userFile = UserFile::where('id', $fileId)
            ->where('user_id', $user->id)
            ->first();

        if (!$userFile) {
            return response()->json(['message' => 'File not found'], 404);
        }

        if ($userFile->isExpired()) {
            return response()->json(['message' => 'File has expired'], 410);
        }

        // If this record has an `output_path` (e.g. merge result), return it.
        if ($userFile->output_path) {
            // First try local disk (userfile path is relative to disk root)
            if (Storage::disk('local')->exists($userFile->output_path)) {
                return Storage::disk('local')->download($userFile->output_path, basename($userFile->output_path));
            }

            // Fallback to direct absolute path if needed
            $absolute = storage_path('app/' . $userFile->output_path);
            if (file_exists($absolute)) {
                return response()->download($absolute, basename($absolute));
            }
        }

        // For now, since we're storing files locally, we'll return the first file
        // In a real implementation, you might want to zip multiple files or handle differently
        $metadata = $userFile->metadata ?? [];
        $uploadedFiles = $metadata['uploaded_files'] ?? [];

        if (empty($uploadedFiles)) {
            return response()->json(['message' => 'No files available for download'], 404);
        }

        $firstFile = $uploadedFiles[0];
        $filePath = $firstFile['path'];

        if (!Storage::disk('local')->exists($filePath)) {
            return response()->json(['message' => 'File not found on disk'], 404);
        }

        $originalFilename = basename($filePath);

        return Storage::disk('local')->download($filePath, $originalFilename);
    }
}
