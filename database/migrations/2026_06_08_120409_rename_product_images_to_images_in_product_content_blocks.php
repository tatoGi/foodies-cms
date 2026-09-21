<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rename the JSON key `product_images` → `images` in product_content_blocks
 * where type = 'product_gallery'.
 *
 * The block type definition schema field was renamed to `key: 'images'` but
 * existing rows still carry the old key. This one-time migration aligns them.
 */
return new class extends Migration
{
    public function up(): void
    {
        // JSON_CONTAINS_PATH is MySQL-only; other drivers (SQLite in tests) have no legacy rows to convert.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::table('product_content_blocks')
            ->where('type', 'product_gallery')
            ->whereRaw("JSON_CONTAINS_PATH(data, 'one', '$.product_images')")
            ->lazyById()
            ->each(function (object $block): void {
                $data = json_decode((string) $block->data, true);

                if (! is_array($data) || ! array_key_exists('product_images', $data)) {
                    return;
                }

                $data['images'] = $data['product_images'];
                unset($data['product_images']);

                DB::table('product_content_blocks')
                    ->where('id', $block->id)
                    ->update(['data' => json_encode($data)]);
            });
    }

    public function down(): void
    {
        // JSON_CONTAINS_PATH is MySQL-only; other drivers (SQLite in tests) have no legacy rows to convert.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::table('product_content_blocks')
            ->where('type', 'product_gallery')
            ->whereRaw("JSON_CONTAINS_PATH(data, 'one', '$.images')")
            ->lazyById()
            ->each(function (object $block): void {
                $data = json_decode((string) $block->data, true);

                if (! is_array($data) || ! array_key_exists('images', $data)) {
                    return;
                }

                $data['product_images'] = $data['images'];
                unset($data['images']);

                DB::table('product_content_blocks')
                    ->where('id', $block->id)
                    ->update(['data' => json_encode($data)]);
            });
    }
};
