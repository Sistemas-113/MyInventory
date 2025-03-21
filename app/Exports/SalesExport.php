<?php

namespace App\Exports;

use App\Models\Sale;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class SalesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $sales;

    public function __construct($sales = null) 
    {
        $this->sales = $sales;
    }

    public function collection()
    {
        return $this->sales ?? Sale::query()->with(['client'])->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Cliente',
            'Identificación',
            'Total',
            'Tipo de Pago',
            'Estado',
            'Tasa Interés',
            'Cuotas',
            'Fecha',
            'Ganancia' // Nuevo encabezado
        ];
    }

    public function map($sale): array
    {
        return [
            $sale->id,
            $sale->client->name,
            $sale->client->identification,
            number_format($sale->total_amount, 0, ',', '.'),
            $this->getPaymentType($sale->payment_type),
            $this->getStatus($sale->status),
            $sale->interest_rate ? "{$sale->interest_rate}%" : 'N/A',
            $sale->installments ?? 'N/A',
            $sale->created_at->format('d/m/Y'),
            number_format($this->calculateProfit($sale), 0, ',', '.') // Nueva columna
        ];
    }

    private function calculateProfit($sale): float
    {
        $totalPurchasePrice = $sale->details->sum(function ($detail) {
            return $detail->purchase_price * $detail->quantity;
        });

        return $sale->total_amount - $totalPurchasePrice;
    }

    private function getPaymentType($type): string
    {
        return match($type) {
            'cash' => 'Contado',
            'credit' => 'Crédito',
            default => $type,
        };
    }

    private function getStatus($status): string
    {
        return match($status) {
            'pending' => 'Pendiente',
            'completed' => 'Completada',
            'cancelled' => 'Cancelada',
            default => $status,
        };
    }
}
