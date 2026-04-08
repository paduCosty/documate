<?php

namespace App\Services\Guest;

use App\Models\DailyUsage;
use App\Models\GuestDailyUsage;
use App\Models\User;

/**
 * Unified context for tool controllers.
 *
 * Limit waterfall (evaluated top to bottom, first match wins):
 *   1. Subscribed → always allowed, no usage recorded
 *   2. Within free daily allowance → allowed, record in daily usage
 *   3. Past daily allowance + has credits → allowed, deduct 1 credit
 *   4. Past daily allowance + no credits → blocked
 *
 * Credits are a top-up, not a bypass: the 3 free ops/day are always
 * consumed before any credit is charged.
 */
class UsageContext
{
    private function __construct(
        public readonly ?int            $userId,
        public readonly ?string         $guestId,
        public readonly array           $limits,
        private readonly DailyUsage|GuestDailyUsage $usage,
        private readonly ?User          $user = null,
    ) {}

    public static function forUser(User $user): self
    {
        return new self(
            userId:  $user->id,
            guestId: null,
            limits:  $user->currentPlanLimits(),
            usage:   $user->todayUsage(),
            user:    $user,
        );
    }

    public static function forGuest(
        string          $guestId,
        array           $limits,
        GuestDailyUsage $usage,
    ): self {
        return new self(
            userId:  null,
            guestId: $guestId,
            limits:  $limits,
            usage:   $usage,
            user:    null,
        );
    }

    public function isGuest(): bool
    {
        return $this->guestId !== null;
    }

    /**
     * True only when the actor is completely blocked:
     * past their free daily limit AND has no credits to fall back on.
     */
    public function hasReachedLimit(): bool
    {
        // Pro subscription → never blocked
        if ($this->user?->hasActivePaidPlan()) return false;

        // Still within the free daily allowance → allowed
        if (!$this->rawDailyLimitHit()) return false;

        // Past free ops but has credits → allowed (will deduct on recordUsage)
        if ($this->user?->hasCredits()) return false;

        // No credits and free ops exhausted → blocked
        return true;
    }

    /**
     * Record a completed operation.
     *   - Subscribed: no-op
     *   - Within free allowance: increment daily counter
     *   - Past free allowance + credits: deduct 1 credit
     */
    public function recordUsage(int $bytes, int $ops = 1): void
    {
        // Subscribed users are never counted
        if ($this->user?->hasActivePaidPlan()) return;

        if ($this->rawDailyLimitHit() && $this->user?->hasCredits()) {
            // Free ops exhausted → charge a credit
            $this->user->deductCredit('PDF operation');
            return;
        }

        // Within free allowance → record daily usage (no credit charged)
        $this->usage->recordUsage($bytes, $ops);
    }

    /** Returns the ownership array ready to spread into UserFile::create(). */
    public function ownerField(): array
    {
        return $this->userId !== null
            ? ['user_id' => $this->userId, 'guest_id' => null]
            : ['user_id' => null,          'guest_id' => $this->guestId];
    }

    /** Base temp-storage path segment. */
    public function storagePath(string $tool, string $batchId): string
    {
        $owner = $this->userId ? ('u' . $this->userId) : ('g_' . $this->guestId);
        return 'temp/' . $tool . '/' . $owner . '/' . $batchId;
    }

    // ── Internal ─────────────────────────────────────────────────────────────

    /** Whether the daily op/byte counters have hit their cap. */
    private function rawDailyLimitHit(): bool
    {
        return $this->usage->hasReachedLimit(
            $this->limits['operations_per_day'],
            $this->limits['total_bytes_per_day'],
        );
    }
}
