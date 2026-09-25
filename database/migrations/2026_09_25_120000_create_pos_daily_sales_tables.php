<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Daily in-store sales sent by the POS (one row per device and day), for the admin sales statistics.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_daily_sales', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pos_device_id')->constrained('pos_devices')->cascadeOnDelete();
            $table->date('sales_date');
            $table->unsignedInteger('bills')->default(0);
            $table->decimal('gross', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('net', 12, 2)->default(0);
            $table->decimal('cash', 12, 2)->default(0);
            $table->decimal('card', 12, 2)->default(0);
            $table->decimal('other', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['pos_device_id', 'sales_date']);
        });

        Schema::create('pos_daily_product_sales', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pos_device_id')->constrained('pos_devices')->cascadeOnDelete();
            $table->date('sales_date');
            $table->unsignedBigInteger('external_id');
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('title');
            $table->unsignedInteger('quantity')->default(0);
            $table->decimal('revenue', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['pos_device_id', 'sales_date', 'external_id'], 'pos_daily_product_sales_unique');
            $table->index('sales_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_daily_product_sales');
        Schema::dropIfExists('pos_daily_sales');
    }
};
