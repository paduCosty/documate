<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'type',
        'description',
        'stripe_session_id',
        'metadata',
    ];

    protected $casts = [
        'amount'   => 'integer',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Scopes for filtering by transaction type. */
    public function scopePurchases($query) { return $query->where('type', 'purchase'); }
    public function scopeUsage($query)     { return $query->where('type', 'usage');    }
}
