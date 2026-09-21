<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Media;
use App\Services\MediaConversionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Throwable;

class ConvertMediaToWebp extends Command
{
    protected $signature = 'media:convert-webp
        {--dry-run : Show what would be converted without writing files or database updates}
        {--delete-original : Delete original JPG/PNG/JFIF/BMP files after successful conversion}
        {--limit= : Maximum number of media rows to process}
        {--overwrite : Recreate WebP files even when the target already exists}';

    protected $description = 'Convert existing uploaded raster images to WebP and update stored CMS paths.';

    public function __construct(
        private readonly MediaConversionService $conversionService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $deleteOriginal = (bool) $this->option('delete-original');
        $overwrite = (bool) $this->option('overwrite');
        $limit = $this->option('limit') !== null ? max(1, (int) $this->option('limit')) : null;

        if (! $dryRun && ! function_exists('imagewebp')) {
            $this->error('PHP GD WebP support is not enabled. Rebuild the app container after updating docker/php/Dockerfile.');

            return self::FAILURE;
        }

        $query = Media::withTrashed()
            ->where('type', 'image')
            ->whereNotNull('path')
            ->orderBy('id');

        if ($limit !== null) {
            $query->limit($limit);
        }

        $processed = 0;
        $converted = 0;
        $skipped = 0;
        $failed = 0;

        $query->chunkById(100, function ($items) use (
            $dryRun,
            $deleteOriginal,
            $overwrite,
            &$processed,
            &$converted,
            &$skipped,
            &$failed
        ): void {
            foreach ($items as $media) {
                $processed++;

                $oldPath = trim((string) $media->path);
                $extension = strtolower(pathinfo($oldPath, PATHINFO_EXTENSION));
                if (! in_array($extension, ['jpg', 'jpeg', 'png', 'jfif', 'bmp'], true)) {
                    if ($extension === 'webp' && $deleteOriginal && ! $dryRun) {
                        $this->deleteOriginalAfterPreviousConversion($media);
                    }

                    $skipped++;

                    continue;
                }

                $newPath = preg_replace('/\.[^.]+$/', '.webp', $oldPath) ?? $oldPath.'.webp';
                if ($newPath === $oldPath) {
                    $skipped++;

                    continue;
                }

                if ($dryRun) {
                    $this->line("[dry-run] media #{$media->id}: {$oldPath} -> {$newPath}");
                    $converted++;

                    continue;
                }

                try {
                    $this->convertOne($media, $oldPath, $newPath, $overwrite, $deleteOriginal);
                    $this->line("converted media #{$media->id}: {$oldPath} -> {$newPath}");
                    $converted++;
                } catch (Throwable $e) {
                    $failed++;
                    $this->error("failed media #{$media->id}: {$oldPath} ({$e->getMessage()})");
                }
            }
        });

        $this->newLine();
        $this->info("Processed: {$processed}; converted: {$converted}; skipped: {$skipped}; failed: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function convertOne(Media $media, string $oldPath, string $newPath, bool $overwrite, bool $deleteOriginal): void
    {
        $diskName = (string) ($media->disk ?: config('media.disk', 'public'));
        $disk = Storage::disk($diskName);

        if (! $disk->exists($oldPath)) {
            throw new \RuntimeException('source file does not exist');
        }

        if ($overwrite || ! $disk->exists($newPath)) {
            $absolutePath = $disk->path($oldPath);
            $encoded = ImageManager::gd()
                ->read($absolutePath)
                ->orient()
                ->toWebp($this->webpQuality());

            $disk->put($newPath, (string) $encoded);
        }

        $size = $disk->size($newPath);

        DB::transaction(function () use ($media, $oldPath, $newPath, $size, $diskName): void {
            $metadata = is_array($media->metadata) ? $media->metadata : [];
            $metadata['original_path_before_webp'] ??= $oldPath;
            $metadata['original_extension'] ??= strtolower(pathinfo($oldPath, PATHINFO_EXTENSION));
            $metadata['extension'] = 'webp';
            $metadata['mime_type'] = 'image/webp';
            $metadata['size_bytes'] = $size;
            $metadata['converted_to_webp'] = true;
            $currentFilename = trim((string) $media->filename);
            $updatedFilename = $currentFilename !== ''
                ? preg_replace('/\.[^.]+$/', '.webp', $currentFilename) ?? $currentFilename.'.webp'
                : basename($newPath);

            $media->forceFill([
                'filename' => $updatedFilename,
                'path' => $newPath,
                'url' => $diskName === 'public' ? Storage::disk('public')->url($newPath) : $media->url,
                'mime_type' => 'image/webp',
                'size' => $size,
                'metadata' => $metadata,
            ])->save();

            $this->replaceStoredReferences($oldPath, $newPath);
        });

        $media->refresh();
        $conversions = $this->conversionService->generateFor($media);
        $metadata = $this->conversionService->extractMetadata($media);
        $metadata['original_path_before_webp'] ??= $oldPath;
        $metadata['converted_to_webp'] = true;
        $metadata['conversions'] = $conversions;

        $thumbnailPath = isset($conversions['thumbnail']) && is_string($conversions['thumbnail'])
            ? $conversions['thumbnail']
            : null;

        $media->forceFill([
            'metadata' => $metadata,
            'thumbnail_path' => $thumbnailPath,
            'thumbnail_url' => $thumbnailPath !== null && $diskName === 'public'
                ? Storage::disk('public')->url($thumbnailPath)
                : null,
        ])->save();

        if ($deleteOriginal && $oldPath !== $newPath && $disk->exists($oldPath)) {
            $disk->delete($oldPath);
        }
    }

    private function deleteOriginalAfterPreviousConversion(Media $media): void
    {
        $metadata = is_array($media->metadata) ? $media->metadata : [];
        $originalPath = trim((string) ($metadata['original_path_before_webp'] ?? ''));
        if ($originalPath === '') {
            return;
        }

        $disk = Storage::disk((string) ($media->disk ?: config('media.disk', 'public')));
        if ($disk->exists($originalPath)) {
            $disk->delete($originalPath);
            $this->line("deleted original for media #{$media->id}: {$originalPath}");
        }
    }

    private function replaceStoredReferences(string $oldPath, string $newPath): void
    {
        foreach ($this->directPathColumns() as [$table, $column]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            DB::table($table)
                ->where($column, $oldPath)
                ->update([$column => $newPath]);
        }

        foreach ($this->jsonPathColumns() as [$table, $column]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            DB::table($table)
                ->where($column, 'like', '%'.$oldPath.'%')
                ->orderBy('id')
                ->chunkById(100, function ($rows) use ($table, $column, $oldPath, $newPath): void {
                    foreach ($rows as $row) {
                        $raw = $row->{$column} ?? null;
                        if (! is_string($raw) || $raw === '') {
                            continue;
                        }

                        $decoded = json_decode($raw, true);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            continue;
                        }

                        $updated = $this->replaceInValue($decoded, $oldPath, $newPath);
                        if ($updated === $decoded) {
                            continue;
                        }

                        DB::table($table)
                            ->where('id', $row->id)
                            ->update([$column => json_encode($updated, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
                    }
                });
        }
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    private function directPathColumns(): array
    {
        return [
            ['pages', 'feature_image'],
            ['posts', 'feature_image'],
            ['products', 'cover_image'],
        ];
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    private function jsonPathColumns(): array
    {
        return [
            ['page_content_blocks', 'data'],
            ['post_content_blocks', 'data'],
            ['product_content_blocks', 'data'],
            ['global_blocks', 'data'],
            ['general_settings', 'value'],
        ];
    }

    private function replaceInValue(mixed $value, string $oldPath, string $newPath): mixed
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $this->replaceInValue($item, $oldPath, $newPath);
            }

            return $value;
        }

        if (! is_string($value)) {
            return $value;
        }

        return str_replace([
            $oldPath,
            '/storage/'.$oldPath,
            'storage/'.$oldPath,
        ], [
            $newPath,
            '/storage/'.$newPath,
            'storage/'.$newPath,
        ], $value);
    }

    private function webpQuality(): int
    {
        return max(1, min(100, (int) config('media.webp_quality', 85)));
    }
}
