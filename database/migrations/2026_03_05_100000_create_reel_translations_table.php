<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reels', function (Blueprint $table) {
            $table->dropColumn(['title', 'slug', 'description']);
        });

        Schema::create('reel_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reel_id')->constrained('reels')->cascadeOnDelete();
            $table->string('locale', 8);
            $table->string('title');
            $table->string('slug');
            $table->text('description');
            $table->timestamps();

            $table->unique(['reel_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reel_translations');

        Schema::table('reels', function (Blueprint $table) {
            $table->string('title')->after('id');
            $table->string('slug')->after('title');
            $table->text('description')->after('slug');
        });
    }
};
