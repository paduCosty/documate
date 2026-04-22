<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents an extraction template stored in the database.
 *
 * System templates (user_id = null) are seeded and shared across all users.
 * Custom templates belong to a specific user.
 *
 * @property int         $id
 * @property int|null    $user_id
 * @property string      $slug
 * @property string      $name
 * @property string|null $description
 * @property string      $prompt_template
 * @property array       $output_schema
 * @property bool        $is_system
 * @property bool        $active
 * @property array|null  $metadata
 */
class ExtractionTemplate extends Model
{
    protected $fillable = [
        'user_id',
        'slug',
        'name',
        'description',
        'prompt_template',
        'output_schema',
        'is_system',
        'active',
        'metadata',
    ];

    protected $casts = [
        'output_schema' => 'array',
        'metadata'      => 'array',
        'is_system'     => 'boolean',
        'active'        => 'boolean',
    ];

    // ─── Relationships ────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeSystem($query)
    {
        return $query->whereNull('user_id')->where('is_system', true);
    }

    /**
     * Returns all templates visible to a given user:
     * all system templates + the user's own custom templates.
     */
    public function scopeVisibleTo($query, ?int $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->whereNull('user_id');

            if ($userId) {
                $q->orWhere('user_id', $userId);
            }
        });
    }

    // ─── Finders ──────────────────────────────────────────────────────────

    /**
     * Finds a template by slug, visible to the given user.
     * System templates are always visible. Returns null if not found.
     */
    public static function findBySlug(string $slug, ?int $userId = null): ?static
    {
        return static::active()
            ->visibleTo($userId)
            ->where('slug', $slug)
            ->first();
    }
}
