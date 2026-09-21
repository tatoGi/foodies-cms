<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use HasFactory;
    use HasMedia;
    use SoftDeletes;

    protected $table = 'posts';

    protected $guarded = [];

    protected $casts = [
        'block_types' => 'array',
        'published' => 'boolean',
        'published_at' => 'datetime',
        'sort_order' => 'integer',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(PostTranslation::class, 'post_id');
    }

    public function slugAliases(): HasMany
    {
        return $this->hasMany(PostSlugAlias::class, 'post_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(PostVersion::class, 'post_id');
    }

    public function pages(): BelongsToMany
    {
        return $this->belongsToMany(Page::class, 'page_post', 'post_id', 'page_id');
    }
}
