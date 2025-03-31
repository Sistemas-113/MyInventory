<?php

namespace App\Exports;

use App\Models\Sale;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OverdueCreditsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function query()
    {
        return Sale::query()
            ->with(['client', 'installments' => function($query) {
                $query->orderBy('installment_number');
            }, 'details', 'payments'])
            ->where('payment_type', 'credit')
            ->whereHas('installments', function($query) {
                $query->where('status', '!=', 'paid')
                    ->where('due_date', '<', now())
                    ->orderBy('due_date', 'asc');
            });
    }

    public function headings(): array
    {
        return [
            'Cliente',
            'Identificación',
            'Teléfono',
            'Fecha Venta',
            'Valor Crédito',
            'Cuotas Vencidas',
            'Días de Mora',
            'Valor en Mora',
            'Total Pagado',
            'Saldo Pendiente',
            'Última Cuota Pagada',
            'Próximo Vencimiento',
            'Detalle Mora',
        ];
    }

    public function map($sale): array
    {
        $installments = $sale->installments()->get();
        
        $overdueInstallments = $installments->filter(function($installment) {
            return $installment->status !== 'paid' && 
                   $installment->due_date->isPast();
        })->sortBy('due_date');

        $oldestOverdue = $overdueInstallments->first();
        $daysOverdue = $oldestOverdue ? $oldestOverdue->due_date->diffInDays(now()) : 0;
        
        $totalPaid = $sale->payments()->sum('amount');
        $totalPending = $sale->total_amount - $totalPaid;
        
        $lastPaidInstallment = $installments
            ->where('status', 'paid')
            ->sortByDesc('paid_date')
            ->first();
            
        $nextDueInstallment = $installments
            ->filter(function($installment) {
                return $installment->status !== 'paid' && 
                       $installment->due_date->isFuture();
            })
            ->sortBy('due_date')
            ->first();

        return [
            $sale->client->name,
            $sale->client->identification,
            $sale->client->phone,
            $sale->created_at->format('d/m/Y'),
            '$ ' . number_format($sale->total_amount, 0, ',', '.'),
            $overdueInstallments->count(),
            $daysOverdue,
            '$ ' . number_format($overdueInstallments->sum('amount'), 0, ',', '.'),
            '$ ' . number_format($totalPaid, 0, ',', '.'),
            '$ ' . number_format($totalPending, 0, ',', '.'),
            $lastPaidInstallment?->paid_date?->format('d/m/Y') ?? 'N/A',
            $nextDueInstallment?->due_date->format('d/m/Y') ?? 'N/A',
            $oldestOverdue ? "Cuota {$oldestOverdue->installment_number} vencida desde " . 
                           $oldestOverdue->due_date->format('d/m/Y') : 'N/A',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();

        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'DC2626']],
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
                'borders' => ['allBorders' => ['borderStyle' => 'thin']]
            ],
            
            // Alineaciones por columna
            'A' => ['alignment' => ['horizontal' => 'left']], // Cliente
            'B:C' => ['alignment' => ['horizontal' => 'center']], // Identificación y Teléfono
            'D' => ['alignment' => ['horizontal' => 'center']], // Fecha Venta
            'E:J' => ['alignment' => ['horizontal' => 'right']], // Montos
            'K:L' => ['alignment' => ['horizontal' => 'center']], // Fechas
            'M' => ['alignment' => ['horizontal' => 'left']], // Detalle Mora

            // Resaltar días de mora
            'G2:G' . $lastRow => [
                'conditionalStyles' => [
                    [
                        'conditionType' => 'cellValue',
                        'operator' => 'greaterThan',
                        'cellValue' => '30',
                        'style' => [
                            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'FF0000']],
                            'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true]
                        ]
                    ],
                    [
                        'conditionType' => 'cellValue',
                        'operator' => 'between',
                        'cellValue' => ['15', '30'],
                        'style' => [
                            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'FFA500']]
                        ]
                    ]
                ]
            ],

            // Bordes y formato para todas las celdas
            'A1:M' . $lastRow => [
                'borders' => ['allBorders' => ['borderStyle' => 'thin']],
                'font' => ['size' => 11]
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30, // Cliente
            'B' => 20, // Identificación
            'C' => 15, // Teléfono
            'D' => 15, // Fecha Venta
            'E' => 20, // Valor Crédito
            'F' => 15, // Cuotas Vencidas
            'G' => 15, // Días de Mora
            'H' => 20, // Valor en Mora
            'I' => 20, // Total Pagado
            'J' => 20, // Saldo Pendiente
            'K' => 15, // Última Cuota Pagada
            'L' => 15, // Próximo Vencimiento
            'M' => 40, // Detalle Mora
        ];
    }
}
