<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlockCategory extends Model
{
    use HasFactory;

    protected $table = 'block_categories';

    protected $guarded = [];
}
