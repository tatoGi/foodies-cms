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
            $table->string('phone', 50)->nullable()->after('email');
            $table->text('address')->nullable()->after('phone');
            $table->index('phone');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->string('customer_phone', 50)->nullable()->after('customer_email');
            $table->text('delivery_address')->nullable()->after('customer_phone');
            $table->index('customer_phone');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex(['customer_phone']);
            $table->dropColumn(['customer_phone', 'delivery_address']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['phone']);
            $table->dropColumn(['phone', 'address']);
        });
    }
};
