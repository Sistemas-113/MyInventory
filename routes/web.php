<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CreditReceiptController;
use App\Http\Controllers\SaleInvoiceController;
use App\Http\Controllers\SaleReceiptController;

// Redirección simple a Filament
Route::get('/', function () {
    return redirect('/admin');
});

Route::redirect('/login', '/admin/login');

Route::get('/credits/{credit}/print-receipt', [CreditReceiptController::class, 'print'])
    ->name('credits.print-receipt')
    ->middleware(['auth']);

Route::get('/sales/{sale}/receipt', [SaleReceiptController::class, 'generate'])
    ->name('sales.generate-receipt')
    ->middleware(['auth']);

Route::get('/sales/{sale}/invoice', [SaleInvoiceController::class, 'generate'])
    ->name('sales.generate-invoice')
    ->middleware(['auth']);

Route::get('/admin/sales/export', function () {
    return Excel::download(new SalesExport(), 'ventas-' . now()->format('Y-m-d') . '.xlsx');
})->name('filament.resources.sales.export')
  ->middleware(['auth']);

Route::get('/admin/sales/analysis/export-credit', function () {
    return Excel::download(new CreditSalesExport(), 'ventas-credito-' . now()->format('Y-m-d') . '.xlsx');
})->name('filament.pages.sales-analysis.export-credit')
  ->middleware(['auth']);

Route::get('/admin/sales/analysis/export-cash', function () {
    return Excel::download(new CashSalesExport(), 'ventas-contado-' . now()->format('Y-m-d') . '.xlsx');
})->name('filament.pages.sales-analysis.export-cash')
  ->middleware(['auth']);
