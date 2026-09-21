<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCategory extends Model
{
    protected $fillable = [
        'external_source',
        'external_id',
        'sort_order',
        'is_active',
        'show_on_menu_board',
        'image',
    ];

    protected $casts = [
        'external_id' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'show_on_menu_board' => 'boolean',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(ProductCategoryTranslation::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
