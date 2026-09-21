<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_chunks', function (Blueprint $table) {
            $table->id();
            $table->string('source_type', 50);          // product / page / block
            $table->unsignedBigInteger('source_id');
            $table->string('title')->nullable();
            $table->text('content');                    // ტექსტი, რომელიც მოდელს მიეწოდება
            $table->json('meta')->nullable();           // ფასი, slug, url...
            $table->json('embedding');                  // float[] (768)
            $table->string('content_hash', 64);         // ხელახალი embedding-ის ასარიდებლად
            $table->timestamps();

            $table->index(['source_type', 'source_id']);
            $table->index('content_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_chunks');
    }
};
