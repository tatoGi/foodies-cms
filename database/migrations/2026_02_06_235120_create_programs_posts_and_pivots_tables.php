<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('slug', 255)->unique();
            $table->string('icon', 255)->nullable();
            $table->string('cover_image', 500)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('published')->default(false);
            $table->integer('view_count')->default(0);
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->timestamp('created_at', 0)->useCurrent();
            $table->timestamp('updated_at', 0)->useCurrent()->useCurrentOnUpdate();

            $table->foreign('parent_id')->references('id')->on('programs')->nullOnDelete();
            $table->foreign('created_by_id')->references('id')->on('admin_users')->nullOnDelete();
            $table->foreign('updated_by_id')->references('id')->on('admin_users')->nullOnDelete();

            $table->index('parent_id');
            $table->index('slug');
            $table->index('is_active');
            $table->index('is_featured');
            $table->index('published');
            $table->index('sort_order');
        });

        Schema::create('program_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('program_id');
            $table->string('locale', 8);
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->text('description')->nullable();
            $table->text('content')->nullable();
            $table->string('meta_title', 255)->nullable();
            $table->text('meta_description')->nullable();
            $table->timestamp('created_at', 0)->useCurrent();
            $table->timestamp('updated_at', 0)->useCurrent()->useCurrentOnUpdate();

            $table->foreign('program_id')->references('id')->on('programs')->cascadeOnDelete();

            $table->unique(['program_id', 'locale'], 'program_id_locale_unique');
            $table->index('locale');
            $table->index('slug');
        });

        Schema::create('program_slug_aliases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('program_id');
            $table->string('locale', 8);
            $table->string('slug', 255)->unique();
            $table->timestamp('created_at', 0)->useCurrent();

            $table->foreign('program_id')->references('id')->on('programs')->cascadeOnDelete();

            $table->index('program_id');
            $table->index('locale');
        });

        Schema::create('program_content_blocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('translation_id');
            $table->string('type', 50);
            $table->json('data');
            $table->integer('sort_order')->default(0);
            $table->timestamp('created_at', 0)->useCurrent();
            $table->timestamp('updated_at', 0)->useCurrent()->useCurrentOnUpdate();

            $table->foreign('translation_id')->references('id')->on('program_translations')->cascadeOnDelete();

            $table->index('translation_id');
            $table->index('type');
            $table->index('sort_order');
        });

        Schema::create('program_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('program_id');
            $table->string('locale', 8);
            $table->json('snapshot');
            $table->timestamp('created_at', 0)->useCurrent();

            $table->foreign('program_id')->references('id')->on('programs')->cascadeOnDelete();

            $table->index('program_id');
            $table->index('locale');
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('author_id')->nullable();
            $table->string('feature_image', 500)->nullable();
            $table->string('category', 50)->nullable();
            $table->json('block_types')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('published')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->integer('view_count')->default(0);
            $table->timestamp('published_at', 0)->nullable();
            $table->timestamp('created_at', 0)->useCurrent();
            $table->timestamp('updated_at', 0)->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('deleted_at', 0)->nullable();

            $table->foreign('author_id')->references('id')->on('admin_users')->nullOnDelete();

            $table->index('author_id');
            $table->index('published');
            $table->index('published_at');
            $table->index('is_featured');
            $table->index('sort_order');
        });

        Schema::create('post_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_id');
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
            $table->timestamp('created_at', 0)->useCurrent();
            $table->timestamp('updated_at', 0)->useCurrent()->useCurrentOnUpdate();

            $table->foreign('post_id')->references('id')->on('posts')->cascadeOnDelete();

            $table->unique(['post_id', 'locale'], 'post_translations_post_id_locale_unique');
            $table->index('locale');
            $table->index('slug');
        });

        Schema::create('post_slug_aliases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_id');
            $table->string('locale', 8);
            $table->string('slug', 255)->unique();
            $table->timestamp('created_at', 0)->useCurrent();

            $table->foreign('post_id')->references('id')->on('posts')->cascadeOnDelete();

            $table->index('post_id');
            $table->index('locale');
        });

        Schema::create('post_content_blocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('translation_id');
            $table->string('type', 50);
            $table->json('data');
            $table->integer('sort_order')->default(0);
            $table->timestamp('created_at', 0)->useCurrent();
            $table->timestamp('updated_at', 0)->useCurrent()->useCurrentOnUpdate();

            $table->foreign('translation_id')->references('id')->on('post_translations')->cascadeOnDelete();

            $table->index('translation_id');
            $table->index('type');
            $table->index('sort_order');
        });

        Schema::create('page_post', function (Blueprint $table) {
            $table->unsignedBigInteger('page_id');
            $table->unsignedBigInteger('post_id');

            $table->primary(['page_id', 'post_id']);

            $table->foreign('page_id')->references('id')->on('pages')->cascadeOnDelete();
            $table->foreign('post_id')->references('id')->on('posts')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_post');
        Schema::dropIfExists('post_content_blocks');
        Schema::dropIfExists('post_slug_aliases');
        Schema::dropIfExists('post_translations');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('program_versions');
        Schema::dropIfExists('program_content_blocks');
        Schema::dropIfExists('program_slug_aliases');
        Schema::dropIfExists('program_translations');
        Schema::dropIfExists('programs');
    }
};
