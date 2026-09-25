<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One POS device's in-store sales for one day (closed bills), sent by the POS. */
class PosDailySale extends Model
{
    protected $guarded = [];

    protected $casts = [
        'sales_date' => 'date',
        'bills' => 'integer',
        'gross' => 'decimal:2',
        'discount' => 'decimal:2',
        'net' => 'decimal:2',
        'cash' => 'decimal:2',
        'card' => 'decimal:2',
        'other' => 'decimal:2',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(PosDevice::class, 'pos_device_id');
    }
}
