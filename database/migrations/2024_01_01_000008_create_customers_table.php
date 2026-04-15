<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->enum('type', ['individual', 'company'])->default('individual');
            $table->string('tax_number')->nullable();
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customer_quotes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('quote_number')->unique();
            $table->date('quote_date');
            $table->date('valid_until')->nullable();
            $table->unsignedBigInteger('branch_id');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2)->default(0);
            $table->enum('status', ['draft', 'sent', 'accepted', 'rejected', 'expired'])->default('draft');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customer_quote_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_quote_id');
            $table->unsignedBigInteger('product_id');
            $table->decimal('quantity', 15, 3);
            $table->decimal('default_price', 15, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('total', 15, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('customer_quote_id')->nullable();
            $table->string('invoice_number')->unique();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->unsignedBigInteger('branch_id');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('total_cost_fifo', 15, 2)->default(0);
            $table->decimal('profit_amount', 15, 2)->default(0);
            $table->enum('status', ['unpaid', 'partial', 'paid'])->default('unpaid');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customer_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_invoice_id');
            $table->unsignedBigInteger('product_id');
            $table->decimal('quantity', 15, 3);
            $table->decimal('default_price', 15, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('total', 15, 2);
            $table->decimal('cost_egp_fifo', 15, 4)->default(0);
            $table->decimal('profit', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('customer_invoice_id')->nullable();
            $table->unsignedBigInteger('bank_account_id');
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_payments');
        Schema::dropIfExists('customer_invoice_items');
        Schema::dropIfExists('customer_invoices');
        Schema::dropIfExists('customer_quote_items');
        Schema::dropIfExists('customer_quotes');
        Schema::dropIfExists('customers');
    }
};
