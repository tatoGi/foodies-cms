<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostVersion extends Model
{
    use HasFactory;

    protected $table = 'post_versions';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'snapshot' => 'array',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }
}
