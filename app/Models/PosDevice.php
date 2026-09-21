<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosDevice extends Model
{
    protected $fillable = [
        'name',
        'token_hash',
        'signing_secret',
        'is_active',
        'app_version',
        'status',
        'last_seen_at',
        'last_sync_at',
    ];

    protected $hidden = ['token_hash', 'signing_secret'];

    protected $casts = [
        'signing_secret' => 'encrypted',
        'is_active' => 'boolean',
        'status' => 'array',
        'last_seen_at' => 'datetime',
        'last_sync_at' => 'datetime',
    ];
}
