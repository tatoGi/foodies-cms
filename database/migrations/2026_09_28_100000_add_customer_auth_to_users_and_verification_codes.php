<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('google_id', 64)->nullable()->unique()->after('email');
            // Google-only customers have no password.
            $table->string('password')->nullable()->change();
        });

        Schema::create('verification_codes', function (Blueprint $table): void {
            $table->id();
            $table->string('email');
            $table->string('purpose', 32);
            $table->string('code_hash', 64);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->index(['email', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_codes');
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['google_id']);
            $table->dropColumn('google_id');
        });
    }
};
