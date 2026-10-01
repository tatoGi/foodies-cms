<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAddress extends Model
{
    protected $fillable = ['label', 'address_line', 'entrance', 'floor', 'apartment', 'lat', 'lng', 'notes', 'is_default'];

    protected function casts(): array
    {
        return ['lat' => 'float', 'lng' => 'float', 'is_default' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array<string, mixed> */
    public function toApi(): array
    {
        return [
            'id' => (int) $this->id,
            'label' => (string) $this->label,
            'address_line' => (string) $this->address_line,
            'entrance' => $this->entrance,
            'floor' => $this->floor,
            'apartment' => $this->apartment,
            'lat' => (float) $this->lat,
            'lng' => (float) $this->lng,
            'notes' => $this->notes,
            'is_default' => (bool) $this->is_default,
        ];
    }
}
