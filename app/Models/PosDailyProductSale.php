<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** How many of one dish a POS device sold in one day, and for how much. */
class PosDailyProductSale extends Model
{
    protected $guarded = [];

    protected $casts = [
        'sales_date' => 'date',
        'external_id' => 'integer',
        'quantity' => 'integer',
        'revenue' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
