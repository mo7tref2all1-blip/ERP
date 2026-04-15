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
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('name');
            $table->string('code')->unique()->nullable();
            $table->string('type')->nullable();
            $table->decimal('thickness_mm', 8, 2)->nullable();
            $table->string('dimensions')->nullable();
            $table->enum('unit', ['meter', 'board', 'sqm', 'ton', 'piece', 'kg'])->default('piece');
            $table->decimal('default_price', 15, 2)->default(0);
            $table->decimal('min_stock', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->decimal('price', 15, 2);
            $table->date('valid_from');
            $table->unsignedBigInteger('created_by');
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
