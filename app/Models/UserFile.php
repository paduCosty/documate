<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UserFile extends Model
{
    use HasFactory;

    protected $fillable = [
        "uuid",
        "user_id",
        "guest_id",
        "operation_type",
        "original_filenames",
        "input_size_bytes",
        "output_size_bytes",
        "output_path",
        "status",
        "expires_at",
        "processed_at",
        "metadata",
    ];

    protected $casts = [
        "original_filenames" => "array",
        "metadata"           => "array",
        "expires_at"         => "datetime",
        "processed_at"       => "datetime",
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($file) {
            if (empty($file->uuid)) {
                $file->uuid = Str::uuid();
            }
            if (empty($file->expires_at)) {
                $file->expires_at = now()->addHours(24);
            }
        });
    }

    // Scopes for ownership — use in controllers instead of raw where()
    public function scopeOwnedByUser($query, int $userId)
    {
        return $query->where("user_id", $userId);
    }

    public function scopeOwnedByGuest($query, string $guestId)
    {
        return $query->where("guest_id", $guestId);
    }

    /** Returns a stable string ID for storage paths, works for both users and guests. */
    public function ownerId(): string
    {
        return $this->user_id ? "u" . $this->user_id : "g_" . $this->guest_id;
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isCompleted(): bool
    {
        return $this->status === "completed";
    }
}
