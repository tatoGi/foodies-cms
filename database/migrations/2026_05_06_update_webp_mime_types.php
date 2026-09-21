<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Update all media records where path ends with .webp to have correct mime_type
        DB::statement(
            "UPDATE media SET mime_type = 'image/webp' WHERE path LIKE '%.webp'"
        );
    }

    public function down(): void
    {
        // Rollback not needed
    }
};
