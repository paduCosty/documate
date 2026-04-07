<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuestDailyUsage extends Model
{
    protected $fillable = [
        "guest_id",
        "date",
        "operations_count",
        "total_bytes_processed",
    ];

    protected $casts = [
        "date" => "date",
    ];

    public function recordUsage(int $bytes = 0, int $filesCount = 1): void
    {
        $this->increment("operations_count", $filesCount);

        if ($bytes > 0) {
            $this->increment("total_bytes_processed", $bytes);
        }
    }

    public function hasReachedLimit(int $opsLimit, int $bytesLimit): bool
    {
        return $this->operations_count >= $opsLimit
            || $this->total_bytes_processed >= $bytesLimit;
    }
}
