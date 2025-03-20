<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;

class SaleInvoiceController extends Controller
{
    public function generate(Sale $sale)
    {
        $sale->load(['client', 'details.provider']);

        $pdf = PDF::loadView('invoices.sale', [
            'sale' => $sale,
            'client' => $sale->client,
            'details' => $sale->details,
        ]);

        return $pdf->stream('factura-' . $sale->id . '.pdf');
    }
}
