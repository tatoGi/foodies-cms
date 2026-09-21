<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactSubmission extends Model
{
    use HasFactory;

    public const TYPE_MESSAGE = 'message';

    public const TYPE_CALL_REQUEST = 'call_request';

    protected $table = 'contact_submissions';

    protected $guarded = [];

    protected $attributes = [
        'type' => self::TYPE_MESSAGE,
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }
}
