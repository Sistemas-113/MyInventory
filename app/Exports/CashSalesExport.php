<?php

namespace App\Exports;

use App\Models\Sale;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CashSalesExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
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
            ->where('payment_type', 'cash')
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
            'Precio Venta',
            'Ganancia',
            'Margen',
            'Método Pago',
            'Estado',
        ];
    }

    public function map($sale): array
    {
        $costoTotal = $sale->details->sum(function ($detail) {
            return $detail->purchase_price * $detail->quantity;
        });

        $precioVenta = $sale->details->sum(function ($detail) {
            return $detail->unit_price * $detail->quantity;
        });

        $ganancia = $precioVenta - $costoTotal;
        $margen = $precioVenta > 0 ? ($ganancia / $precioVenta) * 100 : 0;

        return [
            $sale->created_at->format('d/m/Y'),
            $sale->client->name,
            $sale->details->pluck('product_name')->join(', '),
            '$ ' . number_format($costoTotal, 0, ',', '.'),
            '$ ' . number_format($precioVenta, 0, ',', '.'),
            '$ ' . number_format($ganancia, 0, ',', '.'),
            number_format($margen, 2, ',', '.') . '%',
            'Contado',
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
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '16A34A']], // Verde para contado
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
                'borders' => ['allBorders' => ['borderStyle' => 'thin']]
            ],
            
            // Alineaciones por columna
            'A' => ['alignment' => ['horizontal' => 'center']], // Fecha
            'B' => ['alignment' => ['horizontal' => 'left']], // Cliente
            'C' => ['alignment' => ['horizontal' => 'left']], // Productos
            'D:G' => ['alignment' => ['horizontal' => 'right']], // Montos
            'H:I' => ['alignment' => ['horizontal' => 'center']], // Método y Estado

            // Formato condicional para ganancias
            'F2:F' . $lastRow => [
                'conditionalStyles' => [
                    [
                        'conditionType' => 'cellValue',
                        'operator' => 'greaterThan',
                        'cellValue' => 0,
                        'style' => [
                            'font' => ['color' => ['rgb' => '16A34A']]
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
            'E' => 20, // Precio Venta
            'F' => 20, // Ganancia
            'G' => 15, // Margen
            'H' => 15, // Método Pago
            'I' => 15, // Estado
        ];
    }
}
