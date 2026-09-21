<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductTranslation extends Model
{
    protected $fillable = [
        'product_id',
        'locale',
        'title',
        'category',
        'slug',
        'excerpt',
        'content',
        'meta_title',
        'meta_description',
        'keywords',
        'focus_keyword',
        'canonical_url',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(ProductContentBlock::class, 'translation_id');
    }
}
