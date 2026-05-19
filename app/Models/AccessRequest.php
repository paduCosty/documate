<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessRequest extends Model
{
    protected $fillable = ['email', 'admin_token', 'user_token', 'status', 'approved_at'];

    protected function casts(): array
    {
        return ['approved_at' => 'datetime'];
    }
}
