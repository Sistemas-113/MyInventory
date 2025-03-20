<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CreditReceiptController;
use App\Http\Controllers\SaleInvoiceController;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/credits/{credit}/print-receipt', [CreditReceiptController::class, 'print'])
    ->name('credits.print-receipt')
    ->middleware(['auth']);

Route::get('/sales/{sale}/invoice', [SaleInvoiceController::class, 'generate'])
    ->name('sales.generate-invoice')
    ->middleware(['auth']);

Route::get('/admin/sales/export', function () {
    return Excel::download(new SalesExport(), 'ventas-' . now()->format('Y-m-d') . '.xlsx');
})->name('filament.resources.sales.export')
  ->middleware(['auth']);
