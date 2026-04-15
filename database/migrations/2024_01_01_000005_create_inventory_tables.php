<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_stock', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('product_id');
            $table->decimal('quantity', 15, 3)->default(0);
            $table->timestamps();

            $table->unique(['branch_id', 'product_id']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('to_branch_id')->nullable();
            $table->enum('type', [
                'purchase',
                'sale',
                'transfer_in',
                'transfer_out',
                'adjustment_add',
                'adjustment_remove',
                'return_to_supplier',
                'return_from_customer',
            ]);
            $table->decimal('quantity', 15, 3);
            $table->decimal('quantity_before', 15, 3)->default(0);
            $table->decimal('quantity_after', 15, 3)->default(0);
            $table->decimal('unit_cost', 15, 4)->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'branch_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });

        Schema::create('stock_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('supplier_invoice_item_id')->nullable();
            $table->decimal('quantity', 15, 3);
            $table->decimal('remaining_quantity', 15, 3);
            $table->decimal('cost_egp', 15, 4);
            $table->decimal('cost_usd', 15, 4)->nullable();
            $table->decimal('dollar_rate', 10, 4)->nullable();
            $table->date('batch_date');
            $table->timestamps();

            $table->index(['product_id', 'branch_id', 'batch_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_batches');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('branch_stock');
    }
};
