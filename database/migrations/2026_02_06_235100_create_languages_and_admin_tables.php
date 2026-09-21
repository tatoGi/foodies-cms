<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 8)->unique();
            $table->string('name', 100);
            $table->string('english_name', 100)->nullable();
            $table->string('georgian_name', 100)->nullable();
            $table->string('flag_emoji', 10)->nullable();
            $table->enum('direction', ['ltr', 'rtl'])->default('ltr');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamp('created_at', 0)->useCurrent();
            $table->timestamp('updated_at', 0)->useCurrent()->useCurrentOnUpdate();

            $table->index('code');
            $table->index('is_active');
            $table->index('is_default');
        });

        Schema::create('admin_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->unique();
            $table->string('slug', 255)->unique();
            $table->text('description')->nullable();
            $table->timestamp('created_at', 0)->useCurrent();
            $table->timestamp('updated_at', 0)->useCurrent()->useCurrentOnUpdate();

            $table->index('slug');
        });

        Schema::create('admin_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('label', 255);
            $table->string('group', 50)->default('general');

            $table->index('group');
        });

        Schema::create('admin_role_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');

            $table->primary(['role_id', 'permission_id']);

            $table->foreign('role_id')->references('id')->on('admin_roles')->cascadeOnDelete();
            $table->foreign('permission_id')->references('id')->on('admin_permissions')->cascadeOnDelete();
        });

        Schema::create('admin_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->string('name', 255);
            $table->string('email', 255)->unique();
            $table->string('password', 255);
            $table->string('avatar', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login', 0)->nullable();
            $table->timestamp('created_at', 0)->useCurrent();
            $table->timestamp('updated_at', 0)->useCurrent()->useCurrentOnUpdate();

            $table->foreign('role_id')->references('id')->on('admin_roles')->restrictOnDelete();

            $table->index('role_id');
            $table->index('email');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_users');
        Schema::dropIfExists('admin_role_permissions');
        Schema::dropIfExists('admin_permissions');
        Schema::dropIfExists('admin_roles');
        Schema::dropIfExists('languages');
    }
};
