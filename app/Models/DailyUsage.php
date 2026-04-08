<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'operations_count',
        'total_bytes_processed',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function recordUsage(int $bytes = 0, int $filesCount = 1): void
    {
        $this->increment('operations_count', $filesCount);

        if ($bytes > 0) {
            $this->increment('total_bytes_processed', $bytes);
        }
    }

    public function hasReachedLimit(int $filesLimit, int $bytesLimit): bool
    {
        // Check both limits: files processed AND total bytes processed
        $filesLimitReached = $this->operations_count >= $filesLimit;
        $bytesLimitReached = $this->total_bytes_processed >= $bytesLimit;
        return $filesLimitReached || $bytesLimitReached;
    }
}