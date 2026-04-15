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
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->string('name');
            $table->string('code')->unique()->nullable(); // كود الصنف
            $table->string('type')->nullable(); // نوع الخشب: صنوبر، بلوط، إلخ
            $table->decimal('thickness_mm', 8, 2)->nullable(); // السمك بالمم
            $table->string('dimensions')->nullable(); // الأبعاد (مثلاً: 240x120 سم)
            $table->enum('unit', ['meter', 'board', 'sqm', 'ton', 'piece', 'kg'])->default('piece'); // وحدة القياس
            $table->decimal('default_price', 15, 2)->default(0); // سعر البيع الافتراضي
            $table->decimal('min_stock', 10, 2)->default(0); // الحد الأدنى للمخزون
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // سجل تاريخ أسعار البيع
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 15, 2);
            $table->date('valid_from');
            $table->foreignId('created_by')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_prices');
        Schema::dropIfExists('products');
    }
};
