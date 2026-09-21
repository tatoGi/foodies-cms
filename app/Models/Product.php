<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'sku',
        'brand',
        'cover_image',
        'colors',
        'price',
        'on_sale',
        'sale_price',
        'category',
        'stock',
        'is_active',
        'block_types',
        'sort_order',
        'is_featured',
        'show_in_reels',
        'published',
        'published_at',
        'is_ordered',
        'ordered_at',
        'ordered_by',
        'is_rented',
        'rented_at',
        'rental_start_date',
        'rental_end_date',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'on_sale' => 'boolean',
        'sale_price' => 'decimal:2',
        'stock' => 'integer',
        'is_active' => 'boolean',
        'colors' => 'array',
        'block_types' => 'array',
        'sort_order' => 'integer',
        'is_featured' => 'boolean',
        'show_in_reels' => 'boolean',
        'published' => 'boolean',
        'published_at' => 'datetime',
        'is_ordered' => 'boolean',
        'ordered_at' => 'datetime',
        'ordered_by' => 'integer',
        'is_rented' => 'boolean',
        'rented_at' => 'datetime',
        'rental_start_date' => 'date',
        'rental_end_date' => 'date',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(ProductTranslation::class, 'product_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function pages(): BelongsToMany
    {
        return $this->belongsToMany(Page::class, 'page_product', 'product_id', 'page_id');
    }
}
