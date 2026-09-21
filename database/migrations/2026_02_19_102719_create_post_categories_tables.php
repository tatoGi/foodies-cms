<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('post_categories')) {
            Schema::create('post_categories', function (Blueprint $table): void {
                $table->id();
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamp('created_at', 0)->useCurrent();
                $table->timestamp('updated_at', 0)->useCurrent()->useCurrentOnUpdate();

                $table->index('is_active');
                $table->index('sort_order');
            });
        }

        if (! Schema::hasTable('post_category_translations')) {
            Schema::create('post_category_translations', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('category_id');
                $table->string('locale', 8);
                $table->string('name', 255);
                $table->string('slug', 255);
                $table->text('description')->nullable();
                $table->timestamp('created_at', 0)->useCurrent();
                $table->timestamp('updated_at', 0)->useCurrent()->useCurrentOnUpdate();

                $table->foreign('category_id')->references('id')->on('post_categories')->cascadeOnDelete();

                $table->unique(['category_id', 'locale'], 'post_category_translations_category_locale_unique');
                $table->index('locale');
                $table->index('slug');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('post_category_translations');
        Schema::dropIfExists('post_categories');
    }
};
