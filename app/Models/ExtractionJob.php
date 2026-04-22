<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Tracks a single PDF extraction job from upload to download.
 *
 * Status lifecycle:  pending → processing → completed
 *                                        ↘ failed
 *
 * @property int         $id
 * @property string      $uuid
 * @property int|null    $user_id
 * @property int|null    $template_id
 * @property int|null    $provider_id
 * @property int|null    $model_id
 * @property string      $original_filename
 * @property int|null    $file_size_bytes
 * @property int|null    $page_count
 * @property string      $status
 * @property string      $output_format
 * @property string|null $output_path
 * @property array|null  $extracted_data
 * @property string|null $error_message
 * @property int|null    $tokens_used
 * @property int|null    $processing_time_ms
 * @property \Carbon\Carbon $expires_at
 * @property \Carbon\Carbon|null $processed_at
 * @property array|null  $metadata
 */
class ExtractionJob extends Model
{
    protected $fillable = [
        'uuid',
        'user_id',
        'guest_id',
        'template_id',
        'provider_id',
        'model_id',
        'original_filename',
        'file_size_bytes',
        'page_count',
        'status',
        'output_format',
        'output_path',
        'extracted_data',
        'error_message',
        'tokens_used',
        'processing_time_ms',
        'expires_at',
        'processed_at',
        'metadata',
    ];

    protected $casts = [
        'extracted_data' => 'array',
        'metadata'       => 'array',
        'expires_at'     => 'datetime',
        'processed_at'   => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $job) {
            if (empty($job->uuid)) {
                $job->uuid = (string) Str::uuid();
            }

            if (empty($job->expires_at)) {
                $job->expires_at = now()->addHours(24);
            }
        });
    }

    // ─── Relationships ────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ExtractionTemplate::class, 'template_id');
    }

    // ─── State helpers ────────────────────────────────────────────────────

    public function isPending(): bool    { return $this->status === 'pending'; }
    public function isProcessing(): bool { return $this->status === 'processing'; }
    public function isCompleted(): bool  { return $this->status === 'completed'; }
    public function isFailed(): bool     { return $this->status === 'failed'; }
    public function isExpired(): bool    { return $this->expires_at?->isPast() ?? false; }

    public function markProcessing(): void
    {
        $this->update(['status' => 'processing']);
    }

    public function markCompleted(
        string $outputPath,
        array  $extractedData,
        int    $tokensUsed,
        int    $processingTimeMs,
        int    $pageCount,
    ): void {
        $this->update([
            'status'             => 'completed',
            'output_path'        => $outputPath,
            'extracted_data'     => $extractedData,
            'tokens_used'        => $tokensUsed,
            'processing_time_ms' => $processingTimeMs,
            'page_count'         => $pageCount,
            'processed_at'       => now(),
        ]);
    }

    public function markFailed(string $errorMessage): void
    {
        $this->update([
            'status'        => 'failed',
            'error_message' => $errorMessage,
            'processed_at'  => now(),
        ]);
    }

    // ─── Storage helpers ──────────────────────────────────────────────────

    /**
     * Returns the relative storage path prefix for this job's output files.
     * Pattern: extractions/{u<userId> or guest}/{uuid}
     */
    public function storagePath(): string
    {
        if ($this->user_id) {
            $owner = "u{$this->user_id}";
        } elseif ($this->guest_id) {
            $owner = "g_{$this->guest_id}";
        } else {
            $owner = 'guest';
        }

        return "extractions/{$owner}/{$this->uuid}";
    }

    // ─── Scopes ───────────────────────────────────────────────────────────

    public function scopeOwnedByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }
}
