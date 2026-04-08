<?php

namespace App\Services\Guest;

use App\Models\GuestDailyUsage;
use App\Models\GuestSession;
use App\Models\User;
use App\Models\UserFile;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GuestService
{
    public const COOKIE_NAME   = 'documate_guest_id';
    public const COOKIE_DAYS   = 60;
    public const OPS_PER_DAY   = 3;
    public const MAX_FILE_MB   = 10;
    public const MAX_BYTES_DAY = 10 * 1024 * 1024;

    public function getGuestId(Request $request): ?string
    {
        return $request->cookie(self::COOKIE_NAME);
    }

    public function generateGuestId(): string
    {
        return (string) Str::uuid();
    }

    public function makeCookie(string $guestId): \Symfony\Component\HttpFoundation\Cookie
    {
        return cookie(
            self::COOKIE_NAME,
            $guestId,
            self::COOKIE_DAYS * 24 * 60,
            '/',
            null,
            false,
            true,
            false,
            'Lax'
        );
    }

    public function getOrCreateSession(string $guestId, Request $request): GuestSession
    {
        return GuestSession::firstOrCreate(
            ['guest_id' => $guestId],
            [
                'ip_address'       => $request->ip(),
                'user_agent'       => substr($request->userAgent() ?? '', 0, 500),
                'last_activity_at' => now(),
            ]
        );
    }

    public function touchSession(string $guestId): void
    {
        GuestSession::where('guest_id', $guestId)
            ->update(['last_activity_at' => now()]);
    }

    public function todayUsage(string $guestId): GuestDailyUsage
    {
        return GuestDailyUsage::firstOrCreate([
            'guest_id' => $guestId,
            'date'     => now()->toDateString(),
        ]);
    }

    public function guestLimits(): array
    {
        return [
            'operations_per_day'  => self::OPS_PER_DAY,
            'total_bytes_per_day' => self::MAX_BYTES_DAY,
            'max_file_size_mb'    => self::MAX_FILE_MB,
            'plan'                => 'guest',
        ];
    }

    /**
     * Total free ops used today across ALL guest sessions originating from an IP.
     * Used to prevent the "clear cookie / re-register / logout" bypass.
     */
    public function ipOpsToday(string $ip): int
    {
        // Only look at sessions active in the last 7 days to avoid stale shared-IP pollution
        $guestIds = GuestSession::where('ip_address', $ip)
            ->where('last_activity_at', '>=', now()->subDays(7))
            ->pluck('guest_id');

        if ($guestIds->isEmpty()) return 0;

        return (int) GuestDailyUsage::whereIn('guest_id', $guestIds)
            ->where('date', now()->toDateString())
            ->sum('operations_count');
    }

    /**
     * Returns a unified context for tool controllers.
     *
     * For guests, cross-checks the IP's total ops today and syncs the
     * current guest's record upward if the IP has already burned through
     * more ops on a different guest_id (cookie-refresh or post-logout abuse).
     */
    public function context(Request $request): UsageContext
    {
        if ($user = $request->user()) {
            return UsageContext::forUser($user);
        }

        $guestId = $this->getGuestId($request) ?? $this->generateGuestId();
        $this->getOrCreateSession($guestId, $request);

        $todayUsage = $this->todayUsage($guestId);

        // IP cross-check: if other guest sessions from the same IP have already
        // used more free ops today, inflate this guest's counter to match.
        // This is persisted so future requests for this guest_id also see it.
        $ipOps = $this->ipOpsToday($request->ip());
        if ($ipOps > $todayUsage->operations_count) {
            $todayUsage->update([
                'operations_count' => min($ipOps, self::OPS_PER_DAY),
            ]);
        }

        return UsageContext::forGuest(
            $guestId,
            $this->guestLimits(),
            $todayUsage,
        );
    }

    /** Move all guest files + usage to a real user account. */
    public function migrateToUser(string $guestId, User $user): void
    {
        UserFile::where('guest_id', $guestId)
            ->whereNull('user_id')
            ->update(['user_id' => $user->id, 'guest_id' => null]);

        $guestUsages = GuestDailyUsage::where('guest_id', $guestId)->get();
        foreach ($guestUsages as $g) {
            $u = $user->dailyUsages()->firstOrCreate(['date' => $g->date]);
            $u->increment('operations_count',      $g->operations_count);
            $u->increment('total_bytes_processed', $g->total_bytes_processed);
        }

        GuestDailyUsage::where('guest_id', $guestId)->delete();
        GuestSession::where('guest_id', $guestId)->delete();
    }

    /** Soft abuse protection — count distinct guests from this IP today. */
    public function guestCountFromIp(string $ip): int
    {
        return GuestSession::where('ip_address', $ip)
            ->whereDate('created_at', now()->toDateString())
            ->count();
    }
}
