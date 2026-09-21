<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Alter existing products table: drop simple name/description, add CMS columns
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['name', 'description']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('cover_image', 500)->nullable()->after('sku');
            $table->json('block_types')->nullable()->after('is_active');
            $table->integer('sort_order')->default(0)->after('block_types');
            $table->boolean('is_featured')->default(false)->after('sort_order');
            $table->boolean('published')->default(false)->after('is_featured');
            $table->timestamp('published_at')->nullable()->after('published');

            $table->index('sort_order');
            $table->index('published');
            $table->index('is_featured');
        });

        Schema::create('product_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('locale', 8);
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->text('excerpt')->nullable();
            $table->text('content')->nullable();
            $table->string('meta_title', 255)->nullable();
            $table->text('meta_description')->nullable();
            $table->text('keywords')->nullable();
            $table->string('focus_keyword', 255)->nullable();
            $table->string('canonical_url', 500)->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'locale'], 'product_translations_product_id_locale_unique');
            $table->index('locale');
            $table->index('slug');
        });

        Schema::create('product_content_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('translation_id')->constrained('product_translations')->cascadeOnDelete();
            $table->string('type', 50);
            $table->json('data');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('translation_id');
            $table->index('type');
            $table->index('sort_order');
        });

        Schema::create('page_product', function (Blueprint $table) {
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            $table->primary(['page_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_product');
        Schema::dropIfExists('product_content_blocks');
        Schema::dropIfExists('product_translations');

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['sort_order']);
            $table->dropIndex(['published']);
            $table->dropIndex(['is_featured']);
            $table->dropColumn(['cover_image', 'block_types', 'sort_order', 'is_featured', 'published', 'published_at']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('name', 255)->after('id');
            $table->text('description')->nullable();
        });
    }
};
