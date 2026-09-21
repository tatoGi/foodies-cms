<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('sku', 100)->unique();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('category', 100)->nullable();
            $table->integer('stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('category');
            $table->index('is_active');
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name', 255);
            $table->string('customer_email', 255);
            $table->decimal('total', 10, 2)->default(0);
            $table->enum('status', ['pending', 'processing', 'completed', 'cancelled'])->default('pending');
            $table->timestamp('ordered_at')->useCurrent();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('ordered_at');
            $table->index('customer_email');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('product_name', 255);
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->timestamps();

            $table->index('order_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('products');
    }
};
