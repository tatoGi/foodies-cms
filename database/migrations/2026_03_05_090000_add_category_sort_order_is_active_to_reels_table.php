<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reels', function (Blueprint $table) {
            $table->string('category')->default('new')->after('thumbnail_url'); // 'sale' | 'project' | 'new'
            $table->unsignedInteger('sort_order')->default(0)->after('category');
            $table->boolean('is_active')->default(true)->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('reels', function (Blueprint $table) {
            $table->dropColumn(['category', 'sort_order', 'is_active']);
        });
    }
};
