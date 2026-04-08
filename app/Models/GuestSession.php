<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuestSession extends Model
{
    protected $fillable = [
        "guest_id",
        "ip_address",
        "user_agent",
        "last_activity_at",
    ];

    protected $casts = [
        "last_activity_at" => "datetime",
    ];

    public function files()
    {
        return $this->hasMany(UserFile::class, "guest_id", "guest_id");
    }

    public function dailyUsages()
    {
        return $this->hasMany(GuestDailyUsage::class, "guest_id", "guest_id");
    }
}
