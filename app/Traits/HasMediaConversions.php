<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Support\Facades\Storage;

trait HasMediaConversions
{
    public function getConversionPath(string $conversion): ?string
    {
        $metadata = $this->metadata ?? [];
        $conversions = is_array($metadata) ? ($metadata['conversions'] ?? []) : [];

        if (! is_array($conversions)) {
            return null;
        }

        $path = $conversions[$conversion] ?? null;

        return is_string($path) && $path !== '' ? $path : null;
    }

    public function getConversionUrl(string $conversion): ?string
    {
        $path = $this->getConversionPath($conversion);

        if ($path === null) {
            return null;
        }

        $disk = (string) ($this->disk ?? config('media.disk', 'public'));

        if ($disk === 'public') {
            return Storage::disk('public')->url($path);
        }

        return route('admin.media.download', ['media' => $this->id, 'conversion' => $conversion]);
    }
}
