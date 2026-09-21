<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductContentBlock extends Model
{
    protected $fillable = [
        'translation_id',
        'type',
        'data',
        'sort_order',
    ];

    protected $casts = [
        'data' => 'array',
        'sort_order' => 'integer',
    ];

    public function translation(): BelongsTo
    {
        return $this->belongsTo(ProductTranslation::class, 'translation_id');
    }
}
