<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class CreditReceiptController extends Controller
{
    public function print(Sale $credit)
    {
        // Cargar las relaciones necesarias
        $credit->load(['client', 'details', 'installments']);

        // Verificar que tenemos los datos necesarios
        if (!$credit->client || !$credit->details || !$credit->installments) {
            abort(404, 'No se encontraron todos los datos necesarios para generar el recibo');
        }

        $pdf = PDF::loadView('receipts.credit', [
            'credit' => $credit,
            'client' => $credit->client,
            'details' => $credit->details()->get(), // Asegurar que sea una colección
            'installments' => $credit->installments()->get() // Asegurar que sea una colección
        ]);

        return $pdf->stream('recibo-' . $credit->id . '.pdf');
    }
}
