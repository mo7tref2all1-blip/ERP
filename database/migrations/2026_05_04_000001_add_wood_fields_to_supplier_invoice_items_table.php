<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_invoice_items', function (Blueprint $table) {
            $table->decimal('m3_quantity', 10, 4)->nullable()->after('notes');
            $table->decimal('usd_price_per_m3', 10, 2)->nullable()->after('m3_quantity');
            $table->decimal('boards_per_m3', 10, 2)->nullable()->after('usd_price_per_m3');
            $table->decimal('customs_usd_price', 10, 2)->nullable()->after('boards_per_m3');
            $table->decimal('customs_exchange_rate', 10, 2)->nullable()->after('customs_usd_price');
            $table->decimal('customs_percentage', 5, 2)->nullable()->after('customs_exchange_rate');
            $table->decimal('total_boards', 10, 2)->nullable()->after('customs_percentage');
            $table->decimal('customs_amount', 10, 2)->nullable()->after('total_boards');
            $table->decimal('cost_per_board', 10, 2)->nullable()->after('customs_amount');
        });
    }

    public function down(): void
    {
        Schema::table('supplier_invoice_items', function (Blueprint $table) {
            $table->dropColumn([
                'm3_quantity', 'usd_price_per_m3', 'boards_per_m3',
                'customs_usd_price', 'customs_exchange_rate', 'customs_percentage',
                'total_boards', 'customs_amount', 'cost_per_board',
            ]);
        });
    }
};
