<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GlobalBlock extends Model
{
    use HasFactory;

    protected $table = 'global_blocks';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'data' => 'array',
    ];
}
