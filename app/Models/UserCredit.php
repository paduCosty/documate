<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCredit extends Model
{
    protected $fillable = ['user_id', 'balance', 'total_purchased'];

    protected $casts = [
        'balance'         => 'integer',
        'total_purchased' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
