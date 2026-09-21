<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model
{
    use HasFactory;
    use HasMedia;
    use SoftDeletes;

    protected $table = 'pages';

    protected $guarded = [];

    protected $casts = [
        'block_types' => 'array',
        'published' => 'boolean',
        'show_in_menu' => 'boolean',
        'is_home' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Page::class, 'parent_id');
    }

    public function templateRef(): BelongsTo
    {
        return $this->belongsTo(PageTemplate::class, 'template', 'slug');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(PageTranslation::class, 'page_id');
    }

    public function slugAliases(): HasMany
    {
        return $this->hasMany(PageSlugAlias::class, 'page_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(PageVersion::class, 'page_id');
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'page_post', 'page_id', 'post_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'page_product', 'page_id', 'product_id');
    }
}
