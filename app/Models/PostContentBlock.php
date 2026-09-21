<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostContentBlock extends Model
{
    use HasFactory;

    protected $table = 'post_content_blocks';

    protected $guarded = [];

    protected $casts = [
        'data' => 'array',
    ];

    public function translation(): BelongsTo
    {
        return $this->belongsTo(PostTranslation::class, 'translation_id');
    }
}
