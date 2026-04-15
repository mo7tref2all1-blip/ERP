<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * هذا الملف يحتوي على كل الـ foreign key constraints للمشروع.
 * يجري بعد إنشاء جميع الجداول لضمان عدم وجود تعارض في الترتيب.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─── users ────────────────────────────────────────────────────────────
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('branch_id')
                ->references('id')->on('branches')
                ->nullOnDelete();
        });

        // ─── products ─────────────────────────────────────────────────────────
        Schema::table('products', function (Blueprint $table) {
            $table->foreign('category_id')
                ->references('id')->on('product_categories')
                ->nullOnDelete();
        });

        // ─── product_prices ───────────────────────────────────────────────────
        Schema::table('product_prices', function (Blueprint $table) {
            $table->foreign('product_id')
                ->references('id')->on('products')
                ->cascadeOnDelete();

            $table->foreign('created_by')
                ->references('id')->on('users')
                ->restrictOnDelete();
        });

        // ─── branch_stock ─────────────────────────────────────────────────────
        Schema::table('branch_stock', function (Blueprint $table) {
            $table->foreign('branch_id')
                ->references('id')->on('branches')
                ->cascadeOnDelete();

            $table->foreign('product_id')
                ->references('id')->on('products')
                ->cascadeOnDelete();
        });

        // ─── stock_movements ──────────────────────────────────────────────────
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreign('product_id')
                ->references('id')->on('products')
                ->restrictOnDelete();

            $table->foreign('branch_id')
                ->references('id')->on('branches')
                ->restrictOnDelete();

            $table->foreign('to_branch_id')
                ->references('id')->on('branches')
                ->nullOnDelete();

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->restrictOnDelete();
        });

        // ─── stock_batches ────────────────────────────────────────────────────
        Schema::table('stock_batches', function (Blueprint $table) {
            $table->foreign('product_id')
                ->references('id')->on('products')
                ->restrictOnDelete();

            $table->foreign('branch_id')
                ->references('id')->on('branches')
                ->restrictOnDelete();

            $table->foreign('supplier_invoice_item_id')
                ->references('id')->on('supplier_invoice_items')
                ->nullOnDelete();
        });

        // ─── supplier_invoices ────────────────────────────────────────────────
        Schema::table('supplier_invoices', function (Blueprint $table) {
            $table->foreign('supplier_id')
                ->references('id')->on('suppliers')
                ->restrictOnDelete();

            $table->foreign('branch_id')
                ->references('id')->on('branches')
                ->restrictOnDelete();

            $table->foreign('created_by')
                ->references('id')->on('users')
                ->restrictOnDelete();
        });

        // ─── supplier_invoice_items ───────────────────────────────────────────
        Schema::table('supplier_invoice_items', function (Blueprint $table) {
            $table->foreign('supplier_invoice_id')
                ->references('id')->on('supplier_invoices')
                ->cascadeOnDelete();

            $table->foreign('product_id')
                ->references('id')->on('products')
                ->restrictOnDelete();
        });

        // ─── supplier_payments ────────────────────────────────────────────────
        Schema::table('supplier_payments', function (Blueprint $table) {
            $table->foreign('supplier_id')
                ->references('id')->on('suppliers')
                ->restrictOnDelete();

            $table->foreign('supplier_invoice_id')
                ->references('id')->on('supplier_invoices')
                ->nullOnDelete();

            $table->foreign('bank_account_id')
                ->references('id')->on('bank_accounts')
                ->restrictOnDelete();

            $table->foreign('created_by')
                ->references('id')->on('users')
                ->restrictOnDelete();
        });

        // ─── account_transactions ─────────────────────────────────────────────
        Schema::table('account_transactions', function (Blueprint $table) {
            $table->foreign('bank_account_id')
                ->references('id')->on('bank_accounts')
                ->restrictOnDelete();

            $table->foreign('created_by')
                ->references('id')->on('users')
                ->restrictOnDelete();
        });

        // ─── customer_quotes ──────────────────────────────────────────────────
        Schema::table('customer_quotes', function (Blueprint $table) {
            $table->foreign('customer_id')
                ->references('id')->on('customers')
                ->restrictOnDelete();

            $table->foreign('branch_id')
                ->references('id')->on('branches')
                ->restrictOnDelete();

            $table->foreign('created_by')
                ->references('id')->on('users')
                ->restrictOnDelete();
        });

        // ─── customer_quote_items ─────────────────────────────────────────────
        Schema::table('customer_quote_items', function (Blueprint $table) {
            $table->foreign('customer_quote_id')
                ->references('id')->on('customer_quotes')
                ->cascadeOnDelete();

            $table->foreign('product_id')
                ->references('id')->on('products')
                ->restrictOnDelete();
        });

        // ─── customer_invoices ────────────────────────────────────────────────
        Schema::table('customer_invoices', function (Blueprint $table) {
            $table->foreign('customer_id')
                ->references('id')->on('customers')
                ->restrictOnDelete();

            $table->foreign('customer_quote_id')
                ->references('id')->on('customer_quotes')
                ->nullOnDelete();

            $table->foreign('branch_id')
                ->references('id')->on('branches')
                ->restrictOnDelete();

            $table->foreign('created_by')
                ->references('id')->on('users')
                ->restrictOnDelete();
        });

        // ─── customer_invoice_items ───────────────────────────────────────────
        Schema::table('customer_invoice_items', function (Blueprint $table) {
            $table->foreign('customer_invoice_id')
                ->references('id')->on('customer_invoices')
                ->cascadeOnDelete();

            $table->foreign('product_id')
                ->references('id')->on('products')
                ->restrictOnDelete();
        });

        // ─── customer_payments ────────────────────────────────────────────────
        Schema::table('customer_payments', function (Blueprint $table) {
            $table->foreign('customer_id')
                ->references('id')->on('customers')
                ->restrictOnDelete();

            $table->foreign('customer_invoice_id')
                ->references('id')->on('customer_invoices')
                ->nullOnDelete();

            $table->foreign('bank_account_id')
                ->references('id')->on('bank_accounts')
                ->restrictOnDelete();

            $table->foreign('created_by')
                ->references('id')->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customer_payments', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['customer_invoice_id']);
            $table->dropForeign(['bank_account_id']);
            $table->dropForeign(['created_by']);
        });

        Schema::table('customer_invoice_items', function (Blueprint $table) {
            $table->dropForeign(['customer_invoice_id']);
            $table->dropForeign(['product_id']);
        });

        Schema::table('customer_invoices', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['customer_quote_id']);
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['created_by']);
        });

        Schema::table('customer_quote_items', function (Blueprint $table) {
            $table->dropForeign(['customer_quote_id']);
            $table->dropForeign(['product_id']);
        });

        Schema::table('customer_quotes', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['created_by']);
        });

        Schema::table('account_transactions', function (Blueprint $table) {
            $table->dropForeign(['bank_account_id']);
            $table->dropForeign(['created_by']);
        });

        Schema::table('supplier_payments', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropForeign(['supplier_invoice_id']);
            $table->dropForeign(['bank_account_id']);
            $table->dropForeign(['created_by']);
        });

        Schema::table('supplier_invoice_items', function (Blueprint $table) {
            $table->dropForeign(['supplier_invoice_id']);
            $table->dropForeign(['product_id']);
        });

        Schema::table('supplier_invoices', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['created_by']);
        });

        Schema::table('stock_batches', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['supplier_invoice_item_id']);
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['to_branch_id']);
            $table->dropForeign(['user_id']);
        });

        Schema::table('branch_stock', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['product_id']);
        });

        Schema::table('product_prices', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['created_by']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
        });
    }
};
