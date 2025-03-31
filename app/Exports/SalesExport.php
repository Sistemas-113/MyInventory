<?php

namespace App\Exports;

use App\Models\Sale;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class SalesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $startDate;
    protected $endDate;
    protected $type;

    public function __construct($startDate = null, $endDate = null, $type = 'all') 
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->type = $type;
    }

    public function collection()
    {
        return Sale::query()
            ->with(['client', 'details', 'installments', 'payments'])
            ->when($this->startDate, fn($q) => $q->whereDate('created_at', '>=', $this->startDate))
            ->when($this->endDate, fn($q) => $q->whereDate('created_at', '<=', $this->endDate))
            ->when($this->type === 'pending', fn($q) => $q->where('status', 'pending'))
            ->when($this->type === 'completed', fn($q) => $q->where('status', 'completed'))
            ->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Fecha',
            'Cliente',
            'Identificación',
            'Total Venta',
            'Tipo Pago',
            'Estado',
            'Costo Productos',
            'Ganancia Bruta',
            'Tasa Interés',
            'Monto Intereses',
            'Total con Intereses',
            'Cuotas Totales',
            'Cuotas Pendientes',
            'Próximo Vencimiento',
            'Monto Pendiente',
            'Productos'
        ];
    }

    public function map($sale): array
    {
        $costoTotal = $sale->details->sum(function ($detail) {
            return $detail->purchase_price * $detail->quantity;
        });

        $ganancia = $sale->total_amount - $costoTotal;
        $montoIntereses = 0;
        $totalConIntereses = $sale->total_amount;

        if ($sale->payment_type === 'credit' && $sale->interest_rate > 0) {
            $montoIntereses = ($sale->total_amount * $sale->interest_rate) / 100;
            $totalConIntereses = $sale->total_amount + $montoIntereses;
        }

        // Corregir el manejo de cuotas usando la relación correctamente
        $cuotasPendientes = $sale->installments()->where('status', 'pending')->get();
        
        $proximoVencimiento = $cuotasPendientes->first()?->due_date;
        $montoPendiente = $cuotasPendientes->sum('amount');

        return [
            $sale->id,
            $sale->created_at->format('d/m/Y'),
            $sale->client->name,
            $sale->client->identification,
            number_format($sale->total_amount, 0, ',', '.'),
            $this->getPaymentType($sale->payment_type),
            $this->getStatus($sale->status),
            number_format($costoTotal, 0, ',', '.'),
            number_format($ganancia, 0, ',', '.'),
            $sale->interest_rate ? "{$sale->interest_rate}%" : 'N/A',
            number_format($montoIntereses, 0, ',', '.'),
            number_format($totalConIntereses, 0, ',', '.'),
            $sale->installments ?? 'N/A',
            $cuotasPendientes->count() ?: 'N/A',
            $proximoVencimiento ? Carbon::parse($proximoVencimiento)->format('d/m/Y') : 'N/A',
            number_format($montoPendiente, 0, ',', '.'),
            $sale->details->pluck('product_name')->join(', ')
        ];
    }

    private function getPaymentType($type): string
    {
        return match($type) {
            'cash' => 'Contado',
            'credit' => 'Crédito',
            'card' => 'Tarjeta',
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
