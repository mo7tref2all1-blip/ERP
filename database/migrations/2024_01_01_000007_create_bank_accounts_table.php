<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم الحساب (البنك الأهلي، خزينة الرئيسية، إلخ)
            $table->enum('type', ['bank', 'cash'])->default('cash');
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('account_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_account_id')->constrained();
            $table->enum('type', ['in', 'out']); // وارد / صادر
            $table->decimal('amount', 15, 2);
            $table->string('reference_type')->nullable(); // App\Models\CustomerPayment
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('description')->nullable();
            $table->date('transaction_date');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['bank_account_id', 'transaction_date']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_transactions');
        Schema::dropIfExists('bank_accounts');
    }
};
