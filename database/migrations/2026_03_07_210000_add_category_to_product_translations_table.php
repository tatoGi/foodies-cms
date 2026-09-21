<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_translations', function (Blueprint $table) {
            $table->string('category', 100)->nullable()->after('title');
        });

        DB::table('product_translations')
            ->orderBy('id')
            ->select('id', 'product_id')
            ->chunkById(100, function ($translations): void {
                $productIds = collect($translations)
                    ->pluck('product_id')
                    ->map(static fn ($id): int => (int) $id)
                    ->unique()
                    ->values()
                    ->all();

                $categoriesByProduct = DB::table('products')
                    ->whereIn('id', $productIds)
                    ->pluck('category', 'id');

                foreach ($translations as $translation) {
                    $category = trim((string) ($categoriesByProduct[$translation->product_id] ?? ''));

                    if ($category === '') {
                        continue;
                    }

                    DB::table('product_translations')
                        ->where('id', $translation->id)
                        ->update(['category' => $category]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('product_translations', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
