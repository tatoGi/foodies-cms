<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at', 0)->useCurrent()->nullable();
            $table->timestamp('updated_at', 0)->useCurrent()->useCurrentOnUpdate()->nullable();
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('menu_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->string('type', 50)->default('custom');
            $table->string('url', 500)->nullable();
            $table->string('target', 20)->default('_self');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamp('created_at', 0)->useCurrent()->nullable();
            $table->timestamp('updated_at', 0)->useCurrent()->useCurrentOnUpdate()->nullable();

            $table->foreign('menu_id')->references('id')->on('menus')->cascadeOnDelete();
            $table->foreign('parent_id')->references('id')->on('menu_items')->cascadeOnDelete();

            $table->index('menu_id', 'menu_items_menu_id_index');
            $table->index('parent_id', 'menu_items_parent_id_index');
        });

        Schema::create('menu_item_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('menu_item_id');
            $table->string('locale', 8);
            $table->string('label', 255);
            $table->string('slug', 255);
            $table->timestamp('created_at', 0)->useCurrent()->nullable();
            $table->timestamp('updated_at', 0)->useCurrent()->useCurrentOnUpdate()->nullable();

            $table->foreign('menu_item_id')->references('id')->on('menu_items')->cascadeOnDelete();

            $table->unique(['menu_item_id', 'locale'], 'menu_item_translations_item_locale_unique');
            $table->index('locale', 'menu_item_translations_locale_index');
        });

        Schema::create('media_folders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('name', 255);
            $table->string('slug', 255);
            $table->timestamp('created_at', 0)->useCurrent();
            $table->timestamp('updated_at', 0)->useCurrent()->useCurrentOnUpdate();

            $table->foreign('parent_id')->references('id')->on('media_folders')->nullOnDelete();

            $table->index('parent_id');
        });

        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('folder_id')->nullable();
            $table->string('filename', 255);
            $table->string('original_name', 500)->nullable();
            $table->string('disk', 50)->default('public');
            $table->string('path', 500)->nullable();
            $table->string('url', 500)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->string('type', 20)->nullable();
            $table->integer('size');
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->string('alt', 255)->nullable();
            $table->json('alt_text')->nullable();
            $table->json('title')->nullable();
            $table->json('description')->nullable();
            $table->text('caption')->nullable();
            $table->json('tags')->nullable();
            $table->integer('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->string('thumbnail_path', 500)->nullable();
            $table->string('thumbnail_url', 500)->nullable();
            $table->timestamp('created_at', 0)->useCurrent();
            $table->timestamp('updated_at', 0)->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('deleted_at', 0)->nullable();

            $table->foreign('user_id')->references('id')->on('admin_users')->nullOnDelete();
            $table->foreign('folder_id')->references('id')->on('media_folders')->nullOnDelete();

            $table->index('folder_id');
            $table->index('mime_type');
            $table->index('type');
            $table->index('user_id');
            $table->index('filename');
        });

        Schema::create('mediables', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('media_id');
            $table->unsignedBigInteger('mediable_id');
            $table->string('mediable_type', 255);
            $table->string('collection', 100)->default('default');
            $table->integer('sort_order')->default(0);
            $table->timestamp('created_at', 0)->useCurrent();
            $table->timestamp('updated_at', 0)->useCurrent()->useCurrentOnUpdate();

            $table->foreign('media_id')->references('id')->on('media')->cascadeOnDelete();

            $table->index('media_id');
            $table->index(['mediable_id', 'mediable_type']);
            $table->index('collection');
        });

        Schema::create('global_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('locale', 8);
            $table->json('data');
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at', 0)->useCurrent();
            $table->timestamp('updated_at', 0)->useCurrent()->useCurrentOnUpdate();

            $table->unique(['key', 'locale'], 'key_locale_unique');
            $table->index('key');
            $table->index('locale');
        });

        Schema::create('general_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->json('value');
            $table->string('group', 50);
            $table->string('label', 255);
            $table->boolean('is_public')->default(false);
            $table->timestamp('created_at', 0)->useCurrent();
            $table->timestamp('updated_at', 0)->useCurrent()->useCurrentOnUpdate();

            $table->index('group');
            $table->index('key');
        });

        Schema::create('block_type_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();
            $table->string('label', 255);
            $table->text('description')->nullable();
            $table->string('scope', 20);
            $table->string('icon', 50)->nullable();
            $table->json('schema');
            $table->json('default_data')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamp('created_at', 0)->useCurrent();
            $table->timestamp('updated_at', 0)->useCurrent()->useCurrentOnUpdate();

            $table->index('scope');
            $table->index('is_enabled');
            $table->index('key');
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action', 100);
            $table->string('entity_type', 50);
            $table->string('entity_id', 50);
            $table->text('description')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at', 0)->useCurrent();

            $table->index('user_id');
            $table->index('entity_type');
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('block_type_definitions');
        Schema::dropIfExists('general_settings');
        Schema::dropIfExists('global_blocks');
        Schema::dropIfExists('mediables');
        Schema::dropIfExists('media');
        Schema::dropIfExists('media_folders');
        Schema::dropIfExists('menu_item_translations');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('menus');
    }
};
