<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UserFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'operation_type',
        'original_filenames',
        'input_size_bytes',
        'output_size_bytes',
        'output_path',
        'status',
        'expires_at',
        'processed_at',
        'metadata',
    ];

    protected $casts = [
        'original_filenames' => 'array',
        'metadata'           => 'array',
        'expires_at'         => 'datetime',
        'processed_at'       => 'datetime',
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

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}