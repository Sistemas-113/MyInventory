<?php

namespace App\Exports;

use App\Models\Sale;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB;

class SalesAnalyticsExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate = null, $endDate = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function query()
    {
        return Sale::query()
            ->with(['client', 'details'])
            ->when($this->startDate, fn($q) => $q->whereDate('created_at', '>=', $this->startDate))
            ->when($this->endDate, fn($q) => $q->whereDate('created_at', '<=', $this->endDate));
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Cliente',
            'Productos',
            'Costo Total',
            'Venta Total',
            'Ganancia',
            'Margen',
            'Método Pago',
            'Estado'
        ];
    }

    public function map($sale): array
    {
        $totalCost = $sale->details->sum(function ($detail) {
            return $detail->purchase_price * $detail->quantity;
        });

        $totalProfit = $sale->details->sum(function ($detail) {
            return ($detail->unit_price - $detail->purchase_price) * $detail->quantity;
        });

        $margin = $sale->total_amount > 0 ? ($totalProfit / $sale->total_amount) * 100 : 0;

        return [
            $sale->created_at->format('d/m/Y'),
            $sale->client->name,
            $sale->details->pluck('product_name')->join(', '),
            '$ ' . number_format($totalCost, 0, ',', '.'),
            '$ ' . number_format($sale->total_amount, 0, ',', '.'),
            '$ ' . number_format($totalProfit, 0, ',', '.'),
            number_format($margin, 2, ',', '.') . '%',
            match($sale->payment_type) {
                'cash' => 'Contado',
                'credit' => 'Crédito',
                'transfer' => 'Transferencia',
                default => ucfirst($sale->payment_type)
            },
            match($sale->status) {
                'pending' => 'Pendiente',
                'completed' => 'Completado',
                'cancelled' => 'Cancelado',
                default => ucfirst($sale->status)
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '004d99']],
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
                'borders' => ['allBorders' => ['borderStyle' => 'thin']]
            ],
            
            // Alineaciones por columna
            'A' => ['alignment' => ['horizontal' => 'center']], // Fecha
            'B' => ['alignment' => ['horizontal' => 'left']], // Cliente
            'C' => ['alignment' => ['horizontal' => 'left']], // Productos
            'D:F' => ['alignment' => ['horizontal' => 'right']], // Montos
            'G' => ['alignment' => ['horizontal' => 'right']], // Margen
            'H:I' => ['alignment' => ['horizontal' => 'center']], // Estado y Método

            // Formato condicional para márgenes
            'G2:G' . $lastRow => [
                'numberFormat' => ['formatCode' => '#,##0.00"%"'],
                'conditionalStyles' => [
                    [
                        'conditionType' => 'cellValue',
                        'operator' => 'greaterThanOrEqual',
                        'cellValue' => 30,
                        'style' => [
                            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'C6EFCE']],
                            'font' => ['color' => ['rgb' => '006100']]
                        ]
                    ],
                    [
                        'conditionType' => 'cellValue',
                        'operator' => 'lessThan',
                        'cellValue' => 15,
                        'style' => [
                            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'FFD9D9']],
                            'font' => ['color' => ['rgb' => 'DC2626']]
                        ]
                    ]
                ]
            ],

            // Bordes y formato para todas las celdas
            'A1:I' . $lastRow => [
                'borders' => ['allBorders' => ['borderStyle' => 'thin']],
                'font' => ['size' => 11]
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15, // Fecha
            'B' => 30, // Cliente
            'C' => 50, // Productos
            'D' => 20, // Costo Total
            'E' => 20, // Venta Total
            'F' => 20, // Ganancia
            'G' => 15, // Margen
            'H' => 15, // Método Pago
            'I' => 15, // Estado
        ];
    }
}
