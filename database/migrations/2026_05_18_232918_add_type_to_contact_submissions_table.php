<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_submissions', function (Blueprint $table): void {
            $table->string('type', 30)->default('message')->index()->after('id');
            $table->string('email')->nullable()->change();
            $table->text('message')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('contact_submissions', function (Blueprint $table): void {
            $table->dropIndex(['type']);
            $table->dropColumn('type');
            $table->string('email')->nullable(false)->change();
            $table->text('message')->nullable(false)->change();
        });
    }
};
