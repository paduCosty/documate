<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Cashier\Billable;

#[Fillable(['name', 'email', 'password', 'social_provider', 'social_id', 'email_verified_at', 'notification_settings'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, Billable;

    protected function casts(): array
    {
        return [
            'email_verified_at'     => 'datetime',
            'notification_settings' => 'array',
            'password'              => 'hashed',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function files()
    {
        return $this->hasMany(UserFile::class);
    }

    public function dailyUsages()
    {
        return $this->hasMany(DailyUsage::class);
    }

    public function credits()
    {
        return $this->hasOne(UserCredit::class);
    }

    public function creditTransactions()
    {
        return $this->hasMany(CreditTransaction::class)->latest();
    }

    // ── Subscription helpers ─────────────────────────────────────────────────

    public function hasActivePaidPlan(): bool
    {
        $subscription = $this->subscription('default');

        return $subscription
            && $subscription->active()
            && $subscription->stripe_price !== null;
    }

    // ── Credit helpers ───────────────────────────────────────────────────────

    /** Current spendable credit balance (0 if no row yet). */
    public function creditBalance(): int
    {
        return $this->credits?->balance ?? 0;
    }

    public function hasCredits(): bool
    {
        return $this->creditBalance() > 0;
    }

    /**
     * Atomically deduct 1 credit. Returns false if balance was already 0.
     */
    public function deductCredit(string $description = 'PDF operation'): bool
    {
        return DB::transaction(function () use ($description) {
            $credit = UserCredit::where('user_id', $this->id)
                ->lockForUpdate()
                ->first();

            if (!$credit || $credit->balance <= 0) {
                return false;
            }

            $credit->decrement('balance');

            CreditTransaction::create([
                'user_id'     => $this->id,
                'amount'      => -1,
                'type'        => 'usage',
                'description' => $description,
            ]);

            return true;
        });
    }

    // ── Usage tracking ───────────────────────────────────────────────────────

    public function todayUsage(): DailyUsage
    {
        return DailyUsage::firstOrCreate([
            'user_id' => $this->id,
            'date'    => now()->toDateString(),
        ]);
    }

    // ── Plan limits ──────────────────────────────────────────────────────────

    /**
     * Returns the effective limits for file validation (max file size, daily byte cap).
     * Note: credits do NOT change operations_per_day — free ops are always used first.
     * Once the daily op count is exhausted, UsageContext falls back to credits.
     *
     *   subscribed → pro limits (unlimited, 100MB files)
     *   credits    → free op count (3/day), but larger file size (25MB)
     *   free       → 3 ops/day, 10MB files
     */
    public function currentPlanLimits(): array
    {
        if ($this->hasActivePaidPlan()) {
            return [
                'operations_per_day'  => 999999,
                'total_bytes_per_day' => 999999 * 1024 * 1024,
                'max_file_size_mb'    => 100,
                'plan'                => 'pro',
            ];
        }

        // Credit users still consume the 3 free ops first before credits are charged.
        // They get a larger per-file size limit as a perk.
        if ($this->hasCredits()) {
            return [
                'operations_per_day'  => 3,
                'total_bytes_per_day' => 10 * 1024 * 1024,
                'max_file_size_mb'    => 25,
                'plan'                => 'credits',
            ];
        }

        return [
            'operations_per_day'  => 3,
            'total_bytes_per_day' => 10 * 1024 * 1024,
            'max_file_size_mb'    => 10,
            'plan'                => 'free',
        ];
    }
}
