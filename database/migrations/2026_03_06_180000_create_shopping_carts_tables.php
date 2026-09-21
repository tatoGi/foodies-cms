<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shopping_carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('token', 64)->nullable()->unique();
            $table->string('currency', 8)->default('GEL');
            $table->timestamps();

            $table->unique(['user_id']);
        });

        Schema::create('shopping_cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shopping_cart_id')->constrained('shopping_carts')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->unique(['shopping_cart_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopping_cart_items');
        Schema::dropIfExists('shopping_carts');
    }
};
