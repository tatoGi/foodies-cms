<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Media;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Throwable;

class MediaConversionService
{
    public function generateFor(Media $media): array
    {
        if (! $media->isImage()) {
            return [];
        }

        $diskName = (string) ($media->disk ?: config('media.disk', 'public'));
        $disk = Storage::disk($diskName);

        if (! $disk->exists((string) $media->path)) {
            return [];
        }

        $extension = strtolower(pathinfo((string) $media->path, PATHINFO_EXTENSION));
        if (in_array($extension, ['svg'], true)) {
            return [];
        }

        $binary = $disk->get((string) $media->path);
        $manager = ImageManager::gd();
        $conversions = (array) config('media.conversions', []);
        $generated = [];

        foreach ($conversions as $name => $definition) {
            $width = (int) ($definition['width'] ?? 0);
            $height = (int) ($definition['height'] ?? 0);
            $mode = (string) ($definition['mode'] ?? 'contain');

            if ($width <= 0 || $height <= 0) {
                continue;
            }

            try {
                $image = $manager->read($binary)->orient();
                if ($mode === 'cover') {
                    $image = $image->cover($width, $height, 'center');
                } else {
                    $image = $image->scaleDown(width: $width, height: $height);
                }

                $encodedExtension = $this->normalizeExtensionForEncoding($extension);
                $encodedImage = $this->encode($image, $encodedExtension);
                $conversionPath = $this->conversionPath($media, (string) $name, $encodedExtension);

                $disk->put($conversionPath, (string) $encodedImage);
                $generated[(string) $name] = $conversionPath;

                if ((bool) config('media.generate_webp', true)) {
                    $webpPath = $this->conversionPath($media, (string) $name, 'webp');
                    $disk->put($webpPath, (string) $image->toWebp(85));
                    $generated[(string) $name.'_webp'] = $webpPath;
                }
            } catch (Throwable) {
                continue;
            }
        }

        return $generated;
    }

    public function extractMetadata(Media $media): array
    {
        $metadata = [];
        $diskName = (string) ($media->disk ?: config('media.disk', 'public'));
        $disk = Storage::disk($diskName);

        if (! $disk->exists((string) $media->path)) {
            return $metadata;
        }

        $metadata['extension'] = strtolower(pathinfo((string) $media->path, PATHINFO_EXTENSION));
        $metadata['mime_type'] = $media->mime_type;
        $metadata['size_bytes'] = (int) $media->size;

        if ($media->isImage() && function_exists('exif_read_data')) {
            try {
                $absolutePath = $disk->path((string) $media->path);
                $exif = @exif_read_data($absolutePath, null, true, false);
                if (is_array($exif)) {
                    $metadata['exif'] = $this->trimExif($exif);
                }
            } catch (Throwable) {
                // Ignore unreadable EXIF payloads.
            }
        }

        return $metadata;
    }

    private function normalizeExtensionForEncoding(string $extension): string
    {
        return match ($extension) {
            'jpg', 'jpeg' => 'jpg',
            'png' => 'png',
            'gif' => 'gif',
            'bmp' => 'bmp',
            'webp' => 'webp',
            default => 'jpg',
        };
    }

    private function encode($image, string $extension)
    {
        return match ($extension) {
            'jpg' => $image->toJpeg(85),
            'png' => $image->toPng(),
            'gif' => $image->toGif(),
            'bmp' => $image->toBmp(),
            'webp' => $image->toWebp(85),
            default => $image->toJpeg(85),
        };
    }

    private function conversionPath(Media $media, string $conversion, string $extension): string
    {
        $directory = trim(dirname((string) $media->path), '.');
        $prefix = $directory === '' ? '' : $directory.'/';

        return $prefix.'conversions/'.$media->uuid.'-'.$conversion.'.'.$extension;
    }

    private function trimExif(array $exif): array
    {
        $allowedSections = ['IFD0', 'EXIF', 'COMPUTED'];
        $trimmed = [];

        foreach ($allowedSections as $section) {
            if (! isset($exif[$section]) || ! is_array($exif[$section])) {
                continue;
            }

            $trimmed[$section] = $this->sanitizeExifSection(
                array_slice($exif[$section], 0, 20, true)
            );
        }

        return $trimmed;
    }

    private function sanitizeExifSection(array $data): array
    {
        $clean = [];

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                if (! mb_check_encoding($value, 'UTF-8')) {
                    $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8//IGNORE');
                }
                // Drop values that are still not valid UTF-8 (binary blobs)
                if (! mb_check_encoding($value, 'UTF-8')) {
                    continue;
                }
                $clean[$key] = $value;
            } elseif (is_scalar($value)) {
                $clean[$key] = $value;
            }
            // Skip arrays/objects — EXIF nested structures not needed
        }

        return $clean;
    }
}
