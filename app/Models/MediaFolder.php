<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class MediaFolder extends Model
{
    use HasFactory;

    protected $table = 'media_folders';

    protected $guarded = [];

    protected $appends = [
        'path',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(MediaFolder::class, 'parent_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class, 'folder_id');
    }

    public function getPathAttribute(): string
    {
        $parts = [];
        $node = $this;

        while ($node !== null) {
            array_unshift($parts, Str::slug((string) $node->name));
            $node = $node->parent;
        }

        return implode('/', array_filter($parts));
    }
}
