<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class MediaUploadService
{
    public function __construct(
        private readonly MediaConversionService $conversionService,
    ) {}

    /**
     * @param  array{
     *     folder_id?: int|null,
     *     user_id?: int|null,
     *     disk?: string|null,
     *     title?: array<string,string>|string|null,
     *     alt_text?: array<string,string>|string|null,
     *     description?: array<string,string>|string|null
     * }  $options
     */
    public function uploadFile(UploadedFile $file, array $options = []): Media
    {
        $extension = $this->resolveExtension($file);
        $this->guardFile($file, $extension);

        $disk = (string) ($options['disk'] ?? config('media.disk', 'public'));
        $uuid = (string) Str::uuid();
        $originalMimeType = (string) ($file->getMimeType() ?: 'application/octet-stream');
        $type = $this->detectType($extension, $originalMimeType);
        $shouldConvertToWebp = $this->shouldConvertOriginalToWebp($extension, $type);
        $prefix = trim((string) config('media.path_prefix', 'uploads'), '/');
        $relativeDirectory = trim($prefix.'/'.date('Y').'/'.date('m'), '/');
        $storedExtension = $shouldConvertToWebp ? 'webp' : $extension;
        $storedFilename = $uuid.'.'.$storedExtension;
        $path = $relativeDirectory.'/'.$storedFilename;
        $originalName = (string) $file->getClientOriginalName();
        $safeOriginalName = $this->sanitizeOriginalFilename($originalName);
        $normalizedAltText = $this->normalizeTranslatable($options['alt_text'] ?? null);
        $normalizedTitle = $this->normalizeTranslatable($options['title'] ?? null);
        $normalizedDescription = $this->normalizeTranslatable($options['description'] ?? null);

        [$width, $height] = $this->dimensions($file);
        $size = $this->storeUploadedFile($file, $disk, $relativeDirectory, $storedFilename, $shouldConvertToWebp);
        $mimeType = $shouldConvertToWebp ? 'image/webp' : $originalMimeType;

        $media = Media::create([
            'uuid' => $uuid,
            'user_id' => $options['user_id'] ?? null,
            'folder_id' => $options['folder_id'] ?? null,
            'filename' => $safeOriginalName,
            'original_name' => $originalName,
            'disk' => $disk,
            'path' => $path,
            'url' => $disk === 'public' ? Storage::disk($disk)->url($path) : '',
            'mime_type' => $mimeType,
            'type' => $type,
            'size' => $size,
            'width' => $width,
            'height' => $height,
            'alt_text' => $normalizedAltText,
            'title' => $normalizedTitle,
            'description' => $normalizedDescription,
            'alt' => $this->firstTranslatableValue($normalizedAltText),
            'tags' => [],
            'sort_order' => 0,
            'metadata' => [
                'original_filename' => $originalName,
                'original_extension' => $extension,
                'extension' => $storedExtension,
                'converted_to_webp' => $shouldConvertToWebp,
                'conversions' => [],
            ],
        ]);

        $conversions = $this->conversionService->generateFor($media);
        $metadata = $this->conversionService->extractMetadata($media);
        $metadata['conversions'] = $conversions;
        $thumbnailPath = isset($conversions['thumbnail']) && is_string($conversions['thumbnail'])
            ? $conversions['thumbnail']
            : null;

        $media->update([
            'metadata' => $metadata,
            'thumbnail_path' => $thumbnailPath,
            'thumbnail_url' => $thumbnailPath !== null && $disk === 'public'
                ? Storage::disk($disk)->url($thumbnailPath)
                : null,
            'url' => $disk === 'public'
                ? Storage::disk($disk)->url($path)
                : route('admin.media.download', ['media' => $media->id]),
        ]);

        return $media->fresh();
    }

    /**
     * @param  array<int, UploadedFile>  $files
     * @param  array<string, mixed>  $options
     * @return Collection<int, Media>
     */
    public function uploadFiles(array $files, array $options = []): Collection
    {
        $uploaded = collect();

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $uploaded->push($this->uploadFile($file, $options));
        }

        return $uploaded;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function uploadFromUrl(string $url, array $options = []): Media
    {
        $response = Http::timeout(60)->get($url);
        if (! $response->successful()) {
            throw new RuntimeException('Unable to download file from URL.');
        }

        $urlPath = (string) parse_url($url, PHP_URL_PATH);
        $guessedName = basename($urlPath ?: 'remote-file');
        $originalName = $guessedName !== '' ? $guessedName : 'remote-file';
        $mimeType = (string) ($response->header('Content-Type') ?? 'application/octet-stream');

        $tmpPath = tempnam(sys_get_temp_dir(), 'media-url-');
        if ($tmpPath === false) {
            throw new RuntimeException('Unable to create temporary file.');
        }

        file_put_contents($tmpPath, $response->body());

        $uploadedFile = new UploadedFile(
            $tmpPath,
            $originalName,
            $mimeType,
            null,
            true
        );

        try {
            return $this->uploadFile($uploadedFile, $options);
        } finally {
            @unlink($tmpPath);
        }
    }

    /**
     * @param  array{
     *     upload_id: string,
     *     chunk_index: int,
     *     total_chunks: int,
     *     original_name: string,
     *     file: UploadedFile,
     *     options?: array<string,mixed>
     * }  $payload
     * @return array{completed: bool, media: Media|null}
     */
    public function handleChunkUpload(array $payload): array
    {
        $uploadId = trim((string) $payload['upload_id']);
        $chunkIndex = (int) $payload['chunk_index'];
        $totalChunks = (int) $payload['total_chunks'];
        $originalName = (string) $payload['original_name'];
        /** @var UploadedFile $chunk */
        $chunk = $payload['file'];
        $options = $payload['options'] ?? [];

        if ($uploadId === '' || $totalChunks < 1 || $chunkIndex < 0) {
            throw new InvalidArgumentException('Invalid chunk upload payload.');
        }

        $chunkDirectory = storage_path('app/private/tmp/media-chunks/'.$uploadId);
        if (! is_dir($chunkDirectory) && ! mkdir($chunkDirectory, 0775, true) && ! is_dir($chunkDirectory)) {
            throw new RuntimeException('Unable to create chunk directory.');
        }

        $chunkPath = $chunkDirectory.'/'.str_pad((string) $chunkIndex, 6, '0', STR_PAD_LEFT).'.part';
        file_put_contents($chunkPath, file_get_contents($chunk->getRealPath()) ?: '');

        if ($chunkIndex < $totalChunks - 1) {
            return ['completed' => false, 'media' => null];
        }

        $assembledPath = $chunkDirectory.'/assembled-'.Str::uuid()->toString();
        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension !== '') {
            $assembledPath .= '.'.$extension;
        }

        $assembled = fopen($assembledPath, 'wb');
        if ($assembled === false) {
            throw new RuntimeException('Unable to assemble chunked upload.');
        }

        try {
            for ($i = 0; $i < $totalChunks; $i++) {
                $partPath = $chunkDirectory.'/'.str_pad((string) $i, 6, '0', STR_PAD_LEFT).'.part';
                if (! file_exists($partPath)) {
                    throw new RuntimeException('Missing upload chunk part: '.$i);
                }

                $partContent = file_get_contents($partPath);
                if ($partContent === false) {
                    throw new RuntimeException('Unable to read upload chunk.');
                }

                fwrite($assembled, $partContent);
            }
        } finally {
            fclose($assembled);
        }

        $assembledUpload = new UploadedFile(
            $assembledPath,
            $originalName,
            null,
            null,
            true
        );

        try {
            $media = $this->uploadFile($assembledUpload, is_array($options) ? $options : []);
        } finally {
            $this->cleanupChunkDirectory($chunkDirectory);
        }

        return ['completed' => true, 'media' => $media];
    }

    private function guardFile(UploadedFile $file, string $extension): void
    {
        $extension = strtolower(trim($extension));
        $mimeType = strtolower(trim((string) $file->getMimeType()));
        $allowedByType = (array) config('media.allowed_types', []);
        $allowedExtensions = collect($allowedByType)->flatten()->map(static fn ($value): string => strtolower((string) $value))->all();
        $isAllowedByExtension = $extension !== '' && in_array($extension, $allowedExtensions, true);
        $isAllowedByMime = $this->isAllowedMimeType($mimeType);

        if (! $isAllowedByExtension && ! $isAllowedByMime) {
            throw new InvalidArgumentException('This file type is not allowed.');
        }

        $size = (int) $file->getSize();
        $maxUpload = (int) config('media.max_upload_size', 10 * 1024 * 1024);
        $maxImageUpload = (int) config('media.max_image_upload_size', 5 * 1024 * 1024);
        $isImageExtension = in_array($extension, array_map('strtolower', (array) ($allowedByType['image'] ?? [])), true);
        $isImageMime = str_starts_with($mimeType, 'image/');
        $isImage = $isImageExtension || $isImageMime;
        $targetMax = $isImage ? $maxImageUpload : $maxUpload;

        if ($size > $targetMax) {
            throw new InvalidArgumentException('Uploaded file exceeds configured size limit.');
        }
    }

    private function detectType(string $extension, string $mime): string
    {
        $allowedByType = (array) config('media.allowed_types', []);
        foreach ($allowedByType as $type => $extensions) {
            if (in_array($extension, array_map('strtolower', (array) $extensions), true)) {
                return (string) $type;
            }
        }

        if (str_starts_with($mime, 'image/')) {
            return 'image';
        }

        if (str_starts_with($mime, 'video/')) {
            return 'video';
        }

        if (str_starts_with($mime, 'audio/')) {
            return 'audio';
        }

        return 'other';
    }

    private function resolveExtension(UploadedFile $file): string
    {
        $candidates = [
            strtolower(trim((string) $file->getClientOriginalExtension())),
            strtolower(trim((string) $file->guessExtension())),
            strtolower(trim((string) pathinfo((string) $file->getClientOriginalName(), PATHINFO_EXTENSION))),
            strtolower(trim((string) $this->extensionFromMime((string) $file->getMimeType()))),
        ];

        $allowedByType = (array) config('media.allowed_types', []);
        $allowedExtensions = collect($allowedByType)
            ->flatten()
            ->map(static fn ($value): string => strtolower((string) $value))
            ->values()
            ->all();

        foreach ($candidates as $candidate) {
            if ($candidate !== '' && in_array($candidate, $allowedExtensions, true)) {
                return $candidate;
            }
        }

        foreach ($candidates as $candidate) {
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return 'bin';
    }

    private function isAllowedMimeType(string $mimeType): bool
    {
        if ($mimeType === '') {
            return false;
        }

        if (str_starts_with($mimeType, 'image/')) {
            return true;
        }

        if (str_starts_with($mimeType, 'video/')) {
            return true;
        }

        if (str_starts_with($mimeType, 'audio/')) {
            return true;
        }

        $allowedExactMimes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
            'text/csv',
            'application/zip',
            'application/x-zip-compressed',
            'application/x-rar-compressed',
            'application/vnd.rar',
            'application/x-7z-compressed',
            'application/gzip',
            'application/x-tar',
        ];

        return in_array($mimeType, $allowedExactMimes, true);
    }

    private function extensionFromMime(string $mimeType): ?string
    {
        $mimeType = strtolower(trim($mimeType));
        if ($mimeType === '') {
            return null;
        }

        return match ($mimeType) {
            'image/jpeg', 'image/pjpeg' => 'jpg',
            'image/jfif' => 'jfif',
            'image/png', 'image/x-png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            'image/bmp', 'image/x-ms-bmp' => 'bmp',
            'image/vnd.microsoft.icon', 'image/x-icon' => 'ico',
            'image/avif' => 'avif',
            'image/heic' => 'heic',
            'image/heif' => 'heif',
            'video/mp4' => 'mp4',
            'video/quicktime' => 'mov',
            'video/x-msvideo' => 'avi',
            'video/webm' => 'webm',
            'video/x-ms-wmv' => 'wmv',
            'audio/mpeg' => 'mp3',
            'audio/wav', 'audio/x-wav' => 'wav',
            'audio/ogg' => 'ogg',
            'audio/flac' => 'flac',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'text/plain' => 'txt',
            'text/csv' => 'csv',
            'application/zip', 'application/x-zip-compressed' => 'zip',
            'application/x-rar-compressed', 'application/vnd.rar' => 'rar',
            'application/x-7z-compressed' => '7z',
            'application/x-tar' => 'tar',
            'application/gzip' => 'gz',
            default => null,
        };
    }

    private function shouldConvertOriginalToWebp(string $extension, string $type): bool
    {
        if (! (bool) config('media.convert_original_images_to_webp', true)) {
            return false;
        }

        if ($type !== 'image') {
            return false;
        }

        return in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'jfif', 'bmp'], true);
    }

    private function storeUploadedFile(
        UploadedFile $file,
        string $disk,
        string $relativeDirectory,
        string $storedFilename,
        bool $convertToWebp
    ): int {
        if (! $convertToWebp) {
            Storage::disk($disk)->putFileAs($relativeDirectory, $file, $storedFilename);

            return (int) $file->getSize();
        }

        if (! function_exists('imagewebp')) {
            throw new RuntimeException('PHP GD WebP support is not enabled.');
        }

        $path = $file->getRealPath();
        if ($path === false) {
            throw new RuntimeException('Unable to read uploaded image for WebP conversion.');
        }

        try {
            $encoded = ImageManager::gd()
                ->read($path)
                ->orient()
                ->toWebp($this->webpQuality());
        } catch (Throwable $e) {
            throw new RuntimeException('Unable to convert uploaded image to WebP.', previous: $e);
        }

        $binary = (string) $encoded;
        Storage::disk($disk)->put(trim($relativeDirectory.'/'.$storedFilename, '/'), $binary);

        return strlen($binary);
    }

    private function webpQuality(): int
    {
        return max(1, min(100, (int) config('media.webp_quality', 85)));
    }

    /**
     * @return array{0: int|null, 1: int|null}
     */
    private function dimensions(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        if ($path === false) {
            return [null, null];
        }

        $imageSize = @getimagesize($path);
        if (! is_array($imageSize)) {
            return [null, null];
        }

        return [
            isset($imageSize[0]) ? (int) $imageSize[0] : null,
            isset($imageSize[1]) ? (int) $imageSize[1] : null,
        ];
    }

    private function sanitizeOriginalFilename(string $filename): string
    {
        $filename = trim($filename);
        $filename = Str::ascii($filename);
        $filename = preg_replace('/[^A-Za-z0-9\.\-\_\s]/', '', $filename) ?? '';
        $filename = preg_replace('/\s+/', ' ', $filename) ?? '';
        $filename = trim($filename);

        return $filename !== '' ? $filename : 'file';
    }

    /**
     * @param  array<string,string>|string|null  $value
     * @return array<string,string>|null
     */
    private function normalizeTranslatable(array|string|null $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                return null;
            }

            return [app()->getLocale() => $trimmed];
        }

        $normalized = [];
        foreach ($value as $locale => $text) {
            $locale = trim((string) $locale);
            $text = trim((string) $text);
            if ($locale === '' || $text === '') {
                continue;
            }

            $normalized[$locale] = $text;
        }

        return $normalized !== [] ? $normalized : null;
    }

    /**
     * @param  array<string,string>|null  $value
     */
    private function firstTranslatableValue(?array $value): ?string
    {
        if ($value === null || $value === []) {
            return null;
        }

        $first = reset($value);
        if (! is_string($first)) {
            return null;
        }

        $first = trim($first);

        return $first !== '' ? $first : null;
    }

    private function cleanupChunkDirectory(string $chunkDirectory): void
    {
        if (! is_dir($chunkDirectory)) {
            return;
        }

        $files = glob($chunkDirectory.'/*');
        if ($files !== false) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }

        @rmdir($chunkDirectory);
    }
}
