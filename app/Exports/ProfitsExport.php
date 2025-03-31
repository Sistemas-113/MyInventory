<?php

namespace App\Exports;

use App\Models\Sale;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProfitsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
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
            'Tipo Venta',
            'Costo Total',
            'Precio Venta',
            'Ganancia Base',
            'Intereses',
            'Ganancia Total',
            'Margen',
        ];
    }

    public function map($sale): array
    {
        $costoTotal = $sale->details->sum(fn($detail) => $detail->purchase_price * $detail->quantity);
        $precioVenta = $sale->details->sum(fn($detail) => $detail->unit_price * $detail->quantity);
        $gananciaBase = $precioVenta - $costoTotal;
        $intereses = $sale->payment_type === 'credit' ? ($precioVenta * $sale->interest_rate / 100) : 0;
        $gananciaTotal = $gananciaBase + $intereses;
        $margen = $precioVenta > 0 ? ($gananciaTotal / $precioVenta) * 100 : 0;

        return [
            $sale->created_at->format('d/m/Y'),
            $sale->client->name,
            $sale->payment_type === 'credit' ? 'Crédito' : 'Contado',
            '$ ' . number_format($costoTotal, 0, ',', '.'),
            '$ ' . number_format($precioVenta, 0, ',', '.'),
            '$ ' . number_format($gananciaBase, 0, ',', '.'),
            '$ ' . number_format($intereses, 0, ',', '.'),
            '$ ' . number_format($gananciaTotal, 0, ',', '.'),
            number_format($margen, 2, ',', '.') . '%',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '047857']], // Verde oscuro
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
                'borders' => ['allBorders' => ['borderStyle' => 'thin']]
            ],
            
            // Alineaciones por columna
            'A' => ['alignment' => ['horizontal' => 'center']], // Fecha
            'B' => ['alignment' => ['horizontal' => 'left']], // Cliente
            'C' => ['alignment' => ['horizontal' => 'center']], // Tipo Venta
            'D:H' => ['alignment' => ['horizontal' => 'right']], // Montos
            'I' => ['alignment' => ['horizontal' => 'center']], // Margen

            // Formato condicional para márgenes
            'I2:I' . $lastRow => [
                'numberFormat' => ['formatCode' => '#,##0.00"%"'],
                'conditionalStyles' => [
                    [
                        'conditionType' => 'cellValue',
                        'operator' => 'greaterThanOrEqual',
                        'cellValue' => 35,
                        'style' => [
                            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'C6EFCE']],
                            'font' => ['color' => ['rgb' => '006100']]
                        ]
                    ],
                    [
                        'conditionType' => 'cellValue',
                        'operator' => 'lessThan',
                        'cellValue' => 20,
                        'style' => [
                            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'FFD9D9']],
                            'font' => ['color' => ['rgb' => 'DC2626']]
                        ]
                    ]
                ]
            ],

            // Bordes y formato general
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
            'C' => 15, // Tipo Venta
            'D' => 20, // Costo Total
            'E' => 20, // Precio Venta
            'F' => 20, // Ganancia Base
            'G' => 20, // Intereses
            'H' => 20, // Ganancia Total
            'I' => 15, // Margen
        ];
    }
}
