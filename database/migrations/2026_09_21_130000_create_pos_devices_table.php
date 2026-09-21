<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_devices', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->string('token_hash', 64)->unique();
            $table->text('signing_secret');
            $table->boolean('is_active')->default(true);
            $table->string('app_version', 50)->nullable();
            $table->json('status')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_devices');
    }
};
