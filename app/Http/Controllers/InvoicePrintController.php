<?php

namespace App\Http\Controllers;

use App\Models\CustomerInvoice;
use App\Models\SupplierInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class InvoicePrintController extends Controller
{
    public function customerInvoice(CustomerInvoice $invoice): Response
    {
        $invoice->load(['customer', 'branch', 'items.product', 'payments.bankAccount']);

        $pdf = Pdf::loadView('invoices.customer-invoice-print', compact('invoice'))
            ->setPaper('a4', 'portrait')
            ->setOption('dpi', 150);

        return $pdf->download("فاتورة-{$invoice->invoice_number}.pdf");
    }

    public function supplierInvoice(SupplierInvoice $invoice): Response
    {
        $invoice->load(['supplier', 'branch', 'items.product', 'payments.bankAccount']);

        $pdf = Pdf::loadView('invoices.supplier-invoice-print', compact('invoice'))
            ->setPaper('a4', 'portrait')
            ->setOption('dpi', 150);

        return $pdf->download("فاتورة-شراء-{$invoice->invoice_number}.pdf");
    }
}
