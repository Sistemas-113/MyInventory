<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;
use PDF;

class SaleReceiptController extends Controller
{
    public function generate(Sale $sale)
    {
        $client = $sale->client;
        $details = $sale->details;
        $installments = $sale->installments;
        
        $pdf = PDF::loadView('receipts.credit', compact('sale', 'client', 'details', 'installments'));
        
        return $pdf->stream('recibo-' . str_pad($sale->id, 6, '0', STR_PAD_LEFT) . '.pdf');
    }
}
