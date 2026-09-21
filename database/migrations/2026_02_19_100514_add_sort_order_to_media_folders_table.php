<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('media_folders', 'sort_order')) {
            Schema::table('media_folders', function (Blueprint $table): void {
                $table->integer('sort_order')->default(0);
                $table->index('sort_order');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('media_folders', 'sort_order')) {
            Schema::table('media_folders', function (Blueprint $table): void {
                $table->dropIndex(['sort_order']);
                $table->dropColumn('sort_order');
            });
        }
    }
};
