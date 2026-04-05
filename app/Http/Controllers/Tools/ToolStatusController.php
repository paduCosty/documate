<?php

namespace App\Http\Controllers\Tools;

use App\Models\UserFile;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class ToolStatusController extends Controller
{
    /**
     * Show the status page for a file (Inertia render)
     */
    public function show(Request $request, string $uuid)
    {
        $userFile = UserFile::where('uuid', $uuid)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return Inertia::render('tools/Status', [
            'fileUuid' => $uuid,
            'initialStatus' => $userFile,   // trimitem statusul inițial
        ]);
    }

    /**
     * API endpoint for polling (JSON is allowed here because it's called with fetch)
     */
    public function status(Request $request, string $uuid)
    {
        $userFile = UserFile::where('uuid', $uuid)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json($userFile);
    }

    /**
     * Download the final file
     */
    public function download(Request $request, string $uuid)
    {
        $userFile = UserFile::where('uuid', $uuid)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();
        if ($userFile->isExpired()) {
            abort(410, 'This file has expired and is no longer available.');
        }

        if ($userFile->status !== 'completed' || !$userFile->output_path) {
            abort(400, 'File is not ready for download.');
        }
        // dd($userFile->output_path);
        // dd(Storage::disk('local')->exists($userFile->output_path));

        $storagePath = null;

        if (Storage::disk('local')->exists($userFile->output_path)) {
            $storagePath = Storage::disk('local')->path($userFile->output_path);
        } elseif (file_exists(storage_path('app/' . $userFile->output_path))) {
            $storagePath = storage_path('app/' . $userFile->output_path);
        }

        if (!$storagePath) {
            abort(404, 'File not found on storage.');
        }

        $filename = basename($userFile->output_path);

        return response()->download($storagePath, $filename);
    }
}
