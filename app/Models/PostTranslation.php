<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PostTranslation extends Model
{
    use HasFactory;

    protected $table = 'post_translations';

    protected $guarded = [];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(PostContentBlock::class, 'translation_id');
    }
}
