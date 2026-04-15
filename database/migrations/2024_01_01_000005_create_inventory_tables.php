<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // مخزون كل فرع لكل صنف
        Schema::create('branch_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 15, 3)->default(0);
            $table->timestamps();

            $table->unique(['branch_id', 'product_id']);
        });

        // سجل حركات المخزون الكاملة
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('branch_id')->constrained();
            $table->foreignId('to_branch_id')->nullable()->constrained('branches'); // للتحويل بين الفروع
            $table->enum('type', [
                'purchase',     // شراء/توريد
                'sale',         // بيع
                'transfer_in',  // تحويل وارد
                'transfer_out', // تحويل صادر
                'adjustment_add',   // تسوية إضافة
                'adjustment_remove', // تسوية خصم
                'return_to_supplier', // مرتجع للمورد
                'return_from_customer', // مرتجع من العميل
            ]);
            $table->decimal('quantity', 15, 3); // موجب = إضافة، سالب = خصم
            $table->decimal('quantity_before', 15, 3)->default(0); // الكمية قبل الحركة
            $table->decimal('quantity_after', 15, 3)->default(0);  // الكمية بعد الحركة
            $table->decimal('unit_cost', 15, 4)->nullable(); // تكلفة الوحدة وقت الحركة
            $table->string('reference_type')->nullable(); // App\Models\SupplierInvoice
            $table->unsignedBigInteger('reference_id')->nullable(); // رقم الفاتورة
            $table->foreignId('user_id')->constrained();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'branch_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });

        // دفعات الشراء للـ FIFO
        Schema::create('stock_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('branch_id')->constrained();
            $table->unsignedBigInteger('supplier_invoice_item_id')->nullable();
            $table->decimal('quantity', 15, 3); // الكمية الأصلية
            $table->decimal('remaining_quantity', 15, 3); // الكمية المتبقية
            $table->decimal('cost_egp', 15, 4); // التكلفة بالجنيه
            $table->decimal('cost_usd', 15, 4)->nullable(); // التكلفة بالدولار (إن وُجد)
            $table->decimal('dollar_rate', 10, 4)->nullable(); // سعر الدولار وقت الشراء
            $table->date('batch_date'); // تاريخ الدفعة
            $table->timestamps();

            $table->index(['product_id', 'branch_id', 'batch_date']); // للـ FIFO
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_batches');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('branch_stock');
    }
};
