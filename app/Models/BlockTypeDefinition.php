<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlockTypeDefinition extends Model
{
    use HasFactory;

    protected $table = 'block_type_definitions';

    protected $guarded = [];

    protected $casts = [
        'schema' => 'array',
        'default_data' => 'array',
        'is_system' => 'boolean',
        'is_enabled' => 'boolean',
    ];
}
