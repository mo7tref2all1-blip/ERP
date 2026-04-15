<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('tax_number')->nullable();
            $table->decimal('opening_balance', 15, 2)->default(0); // رصيد افتتاحي
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('supplier_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained();
            $table->string('invoice_number')->unique();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->foreignId('branch_id')->constrained(); // الفرع المستلم
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->enum('status', ['unpaid', 'partial', 'paid'])->default('unpaid');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('supplier_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->decimal('quantity', 15, 3);
            $table->decimal('cost_egp', 15, 4); // التكلفة بالجنيه (مُحسوبة أو مُدخلة مباشرة)
            $table->decimal('cost_usd', 15, 4)->nullable(); // التكلفة بالدولار إن وُجدت
            $table->decimal('dollar_rate', 10, 4)->nullable(); // سعر الدولار وقت الإدخال
            $table->decimal('total_egp', 15, 2); // إجمالي = الكمية × التكلفة
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained();
            $table->foreignId('supplier_invoice_id')->nullable()->constrained(); // قد يكون دفع عام
            $table->foreignId('bank_account_id')->constrained('bank_accounts');
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->string('reference_number')->nullable(); // رقم التحويل/الشيك
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_payments');
        Schema::dropIfExists('supplier_invoice_items');
        Schema::dropIfExists('supplier_invoices');
        Schema::dropIfExists('suppliers');
    }
};
