<?php

namespace App\Services\Guest;

use App\Models\DailyUsage;
use App\Models\GuestDailyUsage;
use App\Models\User;

/**
 * Unified context for tool controllers — wraps either an authenticated user
 * or a guest, exposing the same interface so controllers need zero branching.
 */
class UsageContext
{
    private function __construct(
        public readonly ?int            $userId,
        public readonly ?string         $guestId,
        public readonly array           $limits,
        private readonly DailyUsage|GuestDailyUsage $usage,
    ) {}

    public static function forUser(User $user): self
    {
        return new self(
            userId:  $user->id,
            guestId: null,
            limits:  $user->currentPlanLimits(),
            usage:   $user->todayUsage(),
        );
    }

    public static function forGuest(
        string            $guestId,
        array             $limits,
        GuestDailyUsage   $usage,
    ): self {
        return new self(
            userId:  null,
            guestId: $guestId,
            limits:  $limits,
            usage:   $usage,
        );
    }

    public function isGuest(): bool
    {
        return $this->guestId !== null;
    }

    public function hasReachedLimit(): bool
    {
        return $this->usage->hasReachedLimit(
            $this->limits["operations_per_day"],
            $this->limits["total_bytes_per_day"],
        );
    }

    public function recordUsage(int $bytes, int $ops = 1): void
    {
        $this->usage->recordUsage($bytes, $ops);
    }

    /**
     * Returns the ownership array ready to spread into UserFile::create().
     * e.g. ["user_id" => 5] or ["guest_id" => "uuid-..."]
     */
    public function ownerField(): array
    {
        return $this->userId !== null
            ? ["user_id" => $this->userId, "guest_id" => null]
            : ["user_id" => null,        "guest_id" => $this->guestId];
    }

    /** Base temp-storage path segment. */
    public function storagePath(string $tool, string $batchId): string
    {
        $owner = $this->userId ? ("u" . $this->userId) : ("g_" . $this->guestId);
        return "temp/" . $tool . "/" . $owner . "/" . $batchId;
    }
}
