<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_templates', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->timestamp('created_at', 0)->useCurrent();
            $table->timestamp('updated_at', 0)->useCurrent()->useCurrentOnUpdate();

            $table->foreign('created_by_id')->references('id')->on('admin_users')->nullOnDelete();
            $table->foreign('updated_by_id')->references('id')->on('admin_users')->nullOnDelete();

            $table->index('created_by_id');
            $table->index('updated_by_id');
        });

        Schema::create('page_template_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('template_id');
            $table->string('locale', 8);
            $table->string('name', 255);

            $table->foreign('template_id')->references('id')->on('page_templates')->cascadeOnDelete();

            $table->unique(['template_id', 'locale']);
            $table->index('locale');
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('template', 100);
            $table->json('block_types')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('published')->default(false);
            $table->boolean('show_in_menu')->default(false);
            $table->boolean('is_home')->default(false);
            $table->string('feature_image', 500)->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->timestamp('created_at', 0)->useCurrent();
            $table->timestamp('updated_at', 0)->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('deleted_at', 0)->nullable();

            $table->foreign('parent_id')->references('id')->on('pages')->nullOnDelete();
            $table->foreign('created_by_id')->references('id')->on('admin_users')->nullOnDelete();
            $table->foreign('updated_by_id')->references('id')->on('admin_users')->nullOnDelete();

            $table->index('parent_id');
            $table->index('template');
            $table->index('published');
            $table->index('show_in_menu');
            $table->index('is_home');
        });

        Schema::create('page_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('page_id');
            $table->string('locale', 8);
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->string('subtitle', 255)->nullable();
            $table->text('excerpt')->nullable();
            $table->text('content')->nullable();
            $table->text('description')->nullable();
            $table->string('meta_title', 255)->nullable();
            $table->text('meta_description')->nullable();
            $table->text('keywords')->nullable();
            $table->string('focus_keyword', 255)->nullable();
            $table->string('canonical_url', 500)->nullable();
            $table->timestamp('published_at', 0)->nullable();
            $table->timestamp('created_at', 0)->useCurrent();
            $table->timestamp('updated_at', 0)->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('deleted_at', 0)->nullable();

            $table->foreign('page_id')->references('id')->on('pages')->cascadeOnDelete();

            $table->unique(['page_id', 'locale'], 'page_translations_page_id_locale_unique');
            $table->index('locale');
            $table->index('slug');
        });

        Schema::create('page_slug_aliases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('page_id');
            $table->string('locale', 8);
            $table->string('slug', 255)->unique();
            $table->timestamp('created_at', 0)->useCurrent();

            $table->foreign('page_id')->references('id')->on('pages')->cascadeOnDelete();

            $table->index('page_id');
            $table->index('locale');
        });

        Schema::create('page_content_blocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('translation_id');
            $table->string('type', 50);
            $table->json('data');
            $table->integer('sort_order')->default(0);
            $table->timestamp('created_at', 0)->useCurrent();
            $table->timestamp('updated_at', 0)->useCurrent()->useCurrentOnUpdate();

            $table->foreign('translation_id')->references('id')->on('page_translations')->cascadeOnDelete();

            $table->index('translation_id');
            $table->index('type');
            $table->index('sort_order');
        });

        Schema::create('page_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('page_id');
            $table->string('locale', 8);
            $table->json('snapshot');
            $table->timestamp('created_at', 0)->useCurrent();

            $table->foreign('page_id')->references('id')->on('pages')->cascadeOnDelete();

            $table->index('page_id');
            $table->index('locale');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_versions');
        Schema::dropIfExists('page_content_blocks');
        Schema::dropIfExists('page_slug_aliases');
        Schema::dropIfExists('page_translations');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('page_template_translations');
        Schema::dropIfExists('page_templates');
    }
};
