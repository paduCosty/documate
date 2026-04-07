<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;

#[Fillable(['name', 'email', 'password', 'social_provider', 'social_id', 'email_verified_at', 'notification_settings'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, Billable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'notification_settings' => 'array',
            'password' => 'hashed',
        ];
    }

    public function files()
    {
        return $this->hasMany(UserFile::class);
    }

    public function hasActivePaidPlan(): bool
    {
        $subscription = $this->subscription('default');

        return $subscription
            && $subscription->active()
            && $subscription->stripe_price !== null;
    }

    public function todayUsage(): DailyUsage
    {
        return DailyUsage::firstOrCreate([
            'user_id' => $this->id,
            'date'    => now()->toDateString(),
        ]);
    }

    public function dailyUsages()
    {
        return $this->hasMany(DailyUsage::class);
    }

    public function currentPlanLimits(): array
    {
        if (!$this->hasActivePaidPlan()) {
            return [
                'operations_per_day' => 3,        // max files per day
                'total_bytes_per_day' => 10 * 1024 * 1024, // 10 MB per day
                'max_file_size_mb'   => 10,
                'plan'               => 'free'
            ];
        }


        // Pro 
        return [
            'operations_per_day' => 999999,
            'total_bytes_per_day' => 999999 * 1024 * 1024,
            'max_file_size_mb'   => 100,
            'plan'               => 'pro'
        ];
    }
}
