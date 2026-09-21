<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('external_source', 20)->nullable();
            $table->unsignedBigInteger('external_id')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('show_on_menu_board')->default(false);
            $table->string('image', 500)->nullable();
            $table->timestamps();

            $table->unique(['external_source', 'external_id']);
        });

        Schema::create('product_category_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_category_id')->constrained('product_categories')->cascadeOnDelete();
            $table->string('locale', 8);
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['product_category_id', 'locale'], 'product_category_translations_category_locale_unique');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->string('external_source', 20)->nullable()->after('id');
            $table->unsignedBigInteger('external_id')->nullable()->after('external_source');
            $table->boolean('is_available')->default(true)->after('is_active');
            $table->boolean('show_on_menu_board')->default(false)->after('is_available');
            $table->foreignId('product_category_id')->nullable()->after('is_active')
                ->constrained('product_categories')->nullOnDelete();

            $table->unique(['external_source', 'external_id']);
        });

        Schema::create('product_ingredients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedBigInteger('external_id');
            $table->json('name');
            $table->boolean('is_removable')->default(true);
            $table->timestamps();

            $table->unique(['product_id', 'external_id']);
        });

        Schema::create('product_addons', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedBigInteger('external_id');
            $table->json('name');
            $table->decimal('price', 10, 2)->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_addons');
        Schema::dropIfExists('product_ingredients');

        Schema::table('products', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('product_category_id');
            $table->dropUnique(['external_source', 'external_id']);
            $table->dropColumn(['external_source', 'external_id', 'is_available', 'show_on_menu_board']);
        });

        Schema::dropIfExists('product_category_translations');
        Schema::dropIfExists('product_categories');
    }
};
