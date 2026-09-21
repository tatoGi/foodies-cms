<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_chat_logs', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 64)->index();
            $table->text('question');
            $table->text('answer')->nullable();
            $table->string('provider', 20)->nullable();   // groq / gemini / claude
            $table->string('route_reason', 50)->nullable(); // default / keyword / length / history / user / fallback
            $table->unsignedInteger('latency_ms')->nullable();
            $table->boolean('voice')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_chat_logs');
    }
};
