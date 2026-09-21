<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            if (! Schema::hasColumn('products', 'is_ordered')) {
                $table->boolean('is_ordered')->default(false)->after('published_at');
            }

            if (! Schema::hasColumn('products', 'ordered_at')) {
                $table->timestamp('ordered_at')->nullable()->after('is_ordered');
            }

            if (! Schema::hasColumn('products', 'ordered_by')) {
                $table->unsignedBigInteger('ordered_by')->nullable()->after('ordered_at');
            }

            if (! Schema::hasColumn('products', 'is_rented')) {
                $table->boolean('is_rented')->default(false)->after('ordered_by');
            }

            if (! Schema::hasColumn('products', 'rented_at')) {
                $table->timestamp('rented_at')->nullable()->after('is_rented');
            }

            if (! Schema::hasColumn('products', 'rental_start_date')) {
                $table->date('rental_start_date')->nullable()->after('rented_at');
            }

            if (! Schema::hasColumn('products', 'rental_end_date')) {
                $table->date('rental_end_date')->nullable()->after('rental_start_date');
            }
        });

        Schema::table('bog_payments', function (Blueprint $table): void {
            if (! Schema::hasColumn('bog_payments', 'error_message')) {
                $table->text('error_message')->nullable()->after('callback_data');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $columns = [
                'is_ordered',
                'ordered_at',
                'ordered_by',
                'is_rented',
                'rented_at',
                'rental_start_date',
                'rental_end_date',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('bog_payments', function (Blueprint $table): void {
            if (Schema::hasColumn('bog_payments', 'error_message')) {
                $table->dropColumn('error_message');
            }
        });
    }
};
