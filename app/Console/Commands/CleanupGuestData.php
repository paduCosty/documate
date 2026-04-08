<?php

namespace App\Console\Commands;

use App\Models\GuestDailyUsage;
use App\Models\GuestSession;
use App\Models\UserFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupGuestData extends Command
{
    protected $signature   = "guests:cleanup";
    protected $description = "Delete expired guest sessions, usage records, and files.";

    public function handle(): int
    {
        // 1. Delete sessions inactive for more than 7 days
        $sessions = GuestSession::where("last_activity_at", "<", now()->subDays(7))->get();
        $guestIds  = $sessions->pluck("guest_id")->toArray();

        if (!empty($guestIds)) {
            GuestDailyUsage::whereIn("guest_id", $guestIds)->delete();

            // Delete files + their stored outputs
            $files = UserFile::whereIn("guest_id", $guestIds)->get();
            foreach ($files as $file) {
                if ($file->output_path && Storage::disk("local")->exists($file->output_path)) {
                    Storage::disk("local")->delete($file->output_path);
                }
            }
            UserFile::whereIn("guest_id", $guestIds)->delete();

            GuestSession::whereIn("guest_id", $guestIds)->delete();

            $this->info("Cleaned " . count($guestIds) . " inactive guest session(s).");
        }

        // 2. Delete expired guest files that are still lingering
        $expired = UserFile::whereNotNull("guest_id")
            ->where("expires_at", "<", now())
            ->get();

        foreach ($expired as $file) {
            if ($file->output_path && Storage::disk("local")->exists($file->output_path)) {
                Storage::disk("local")->delete($file->output_path);
            }
            $file->delete();
        }

        // 3. Delete guest usage records older than 30 days
        GuestDailyUsage::where("date", "<", now()->subDays(30)->toDateString())->delete();

        $this->info("Guest cleanup complete.");
        return self::SUCCESS;
    }
}
