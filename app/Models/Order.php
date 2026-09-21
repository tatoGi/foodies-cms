<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING_PAYMENT = 'pending_payment';

    public const STATUS_PAID = 'paid';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const REVENUE_STATUSES = [
        self::STATUS_PAID,
        self::STATUS_PROCESSING,
        self::STATUS_COMPLETED,
    ];

    protected $fillable = [
        'customer_name',
        'customer_email',
        'customer_phone',
        'delivery_address',
        'total',
        'status',
        'ordered_at',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'ordered_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->whereIn('status', self::REVENUE_STATUSES);
    }

    public function scopeRevenueRelevant(Builder $query): Builder
    {
        return $query->whereIn('status', self::REVENUE_STATUSES);
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }
}
