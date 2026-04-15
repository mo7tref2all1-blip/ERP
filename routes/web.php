<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

// مسارات طباعة الفواتير (محمية بـ auth)
Route::middleware(['auth'])->group(function () {
    Route::get('/invoices/customer/{invoice}/print', [App\Http\Controllers\InvoicePrintController::class, 'customerInvoice'])
        ->name('customer-invoice.print');
    Route::get('/invoices/supplier/{invoice}/print', [App\Http\Controllers\InvoicePrintController::class, 'supplierInvoice'])
        ->name('supplier-invoice.print');
});
