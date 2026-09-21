<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasMediaConversions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasFactory;
    use HasMediaConversions;
    use SoftDeletes;

    protected $table = 'media';

    protected $guarded = [];

    protected $casts = [
        'tags' => 'array',
        'alt_text' => 'array',
        'title' => 'array',
        'description' => 'array',
        'metadata' => 'array',
        'deleted_at' => 'datetime',
    ];

    protected $appends = [
        'human_readable_size',
        'thumbnail_url',
        'medium_url',
        'full_url',
    ];

    public function folder(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'folder_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'user_id');
    }

    public function links(): HasMany
    {
        return $this->hasMany(Mediable::class, 'media_id');
    }

    public function pages(): MorphToMany
    {
        return $this->morphedByMany(Page::class, 'mediable', 'mediables', 'media_id', 'mediable_id')
            ->withPivot(['collection', 'sort_order'])
            ->withTimestamps();
    }

    public function posts(): MorphToMany
    {
        return $this->morphedByMany(Post::class, 'mediable', 'mediables', 'media_id', 'mediable_id')
            ->withPivot(['collection', 'sort_order'])
            ->withTimestamps();
    }

    public function mediablesFor(string $modelClass): MorphToMany
    {
        return $this->morphedByMany($modelClass, 'mediable', 'mediables', 'media_id', 'mediable_id')
            ->withPivot(['collection', 'sort_order'])
            ->withTimestamps();
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeInFolder($query, ?int $folderId)
    {
        if ($folderId === null) {
            return $query->whereNull('folder_id');
        }

        return $query->where('folder_id', $folderId);
    }

    public function scopeSearch($query, ?string $term)
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function ($inner) use ($term): void {
            $inner
                ->where('filename', 'like', '%'.$term.'%')
                ->orWhere('mime_type', 'like', '%'.$term.'%')
                ->orWhere('path', 'like', '%'.$term.'%')
                ->orWhere('alt_text', 'like', '%'.$term.'%')
                ->orWhere('title', 'like', '%'.$term.'%')
                ->orWhere('description', 'like', '%'.$term.'%');
        });
    }

    public function isImage(): bool
    {
        return $this->type === 'image';
    }

    public function isVideo(): bool
    {
        return $this->type === 'video';
    }

    public function isAudio(): bool
    {
        return $this->type === 'audio';
    }

    public function isDocument(): bool
    {
        return $this->type === 'document';
    }

    public function getHumanReadableSizeAttribute(): string
    {
        $size = (int) $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        $precision = $i === 0 ? 0 : 2;

        return number_format($size, $precision).' '.$units[$i];
    }

    public function getThumbnailUrlAttribute(): string
    {
        return $this->getConversionUrl('thumbnail') ?? $this->resolvePublicUrl();
    }

    public function getMediumUrlAttribute(): string
    {
        return $this->getConversionUrl('medium') ?? $this->resolvePublicUrl();
    }

    public function getFullUrlAttribute(): string
    {
        return $this->resolvePublicUrl();
    }

    public function localizedField(string $field, ?string $locale = null): ?string
    {
        $locale = $locale ?: app()->getLocale();
        $fallbackLocale = config('app.fallback_locale', 'en');
        $raw = $this->getAttribute($field);

        if (is_array($raw)) {
            return $raw[$locale] ?? $raw[$fallbackLocale] ?? (reset($raw) ?: null);
        }

        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded[$locale] ?? $decoded[$fallbackLocale] ?? (reset($decoded) ?: null);
            }

            return $raw;
        }

        return null;
    }

    private function resolvePublicUrl(): string
    {
        if ((string) $this->disk === 'public') {
            return Storage::disk('public')->url((string) $this->path);
        }

        return route('admin.media.download', ['media' => $this->id]);
    }
}
