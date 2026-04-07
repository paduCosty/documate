<?php

namespace App\Http\Controllers\Tools;

use App\Http\Controllers\Controller;
use App\Models\UserFile;
use App\Services\Guest\GuestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ToolStatusController extends Controller
{
    public function __construct(private GuestService $guests) {}

    /** Find a file owned by the current actor (user or guest). */
    private function findOwned(Request $request, string $uuid): UserFile
    {
        $query = UserFile::where("uuid", $uuid);

        if ($user = $request->user()) {
            $query->where("user_id", $user->id);
        } else {
            $guestId = $this->guests->getGuestId($request);
            abort_if(!$guestId, 403, "Access denied.");
            $query->where("guest_id", $guestId);
        }

        return $query->firstOrFail();
    }

    public function show(Request $request, string $uuid)
    {
        $userFile = $this->findOwned($request, $uuid);

        return Inertia::render("tools/Status", [
            "fileUuid"      => $uuid,
            "initialStatus" => $userFile,
        ]);
    }

    public function status(Request $request, string $uuid)
    {
        $userFile = $this->findOwned($request, $uuid);

        return response()->json($userFile);
    }

    public function download(Request $request, string $uuid)
    {
        $userFile = $this->findOwned($request, $uuid);

        if ($userFile->isExpired()) {
            abort(410, "This file has expired and is no longer available.");
        }

        if ($userFile->status !== "completed" || !$userFile->output_path) {
            abort(400, "File is not ready for download.");
        }

        $storagePath = null;

        if (Storage::disk("local")->exists($userFile->output_path)) {
            $storagePath = Storage::disk("local")->path($userFile->output_path);
        } elseif (file_exists(storage_path("app/" . $userFile->output_path))) {
            $storagePath = storage_path("app/" . $userFile->output_path);
        }

        if (!$storagePath) {
            abort(404, "File not found on storage.");
        }

        return response()->download($storagePath, basename($userFile->output_path));
    }
}
