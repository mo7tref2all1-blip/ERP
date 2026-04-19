<?php

namespace App\Http\Controllers;

use App\Models\CustomerInvoice;
use App\Models\SupplierInvoice;

class InvoicePrintController extends Controller
{
    public function customerInvoice(CustomerInvoice $invoice)
    {
        $invoice->load(['customer', 'branch', 'items.product', 'payments.bankAccount']);

        $html = view('invoices.customer-invoice-print', compact('invoice'))->render();

        return $this->streamPdf($html, "فاتورة-{$invoice->invoice_number}.pdf");
    }

    public function supplierInvoice(SupplierInvoice $invoice)
    {
        $invoice->load(['supplier', 'branch', 'items.product', 'payments.bankAccount']);

        $html = view('invoices.supplier-invoice-print', compact('invoice'))->render();

        return $this->streamPdf($html, "فاتورة-شراء-{$invoice->invoice_number}.pdf");
    }

    private function streamPdf(string $html, string $filename)
    {
        $tempDir = storage_path('app/mpdf');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $mpdf = new \Mpdf\Mpdf([
            'mode'         => 'utf-8',
            'format'       => 'A4',
            'default_font' => 'dejavusans',
            'direction'    => 'rtl',
            'tempDir'      => $tempDir,
        ]);

        $mpdf->SetTitle($filename);
        $mpdf->WriteHTML($html);

        $pdf = $mpdf->Output('', 'S');

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
        ]);
    }
}
