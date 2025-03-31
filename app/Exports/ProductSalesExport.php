<?php

namespace App\Exports;

use App\Models\SaleDetail;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB;

class ProductSalesExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
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
        return SaleDetail::query()
            ->select([
                'product_name',
                'identifier',
                'identifier_type',
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(quantity * unit_price) as total_sales'),
                DB::raw('SUM(quantity * purchase_price) as total_cost'),
                DB::raw('SUM(quantity * (unit_price - purchase_price)) as total_profit')
            ])
            ->whereHas('sale', function ($query) {
                $query->when($this->startDate, fn($q) => $q->whereDate('created_at', '>=', $this->startDate))
                    ->when($this->endDate, fn($q) => $q->whereDate('created_at', '<=', $this->endDate));
            })
            ->groupBy('product_name', 'identifier', 'identifier_type')
            ->orderByDesc('total_quantity');
    }

    public function headings(): array
    {
        return [
            'Producto',
            'Identificador',
            'Tipo ID',
            'Cantidad Vendida',
            'Ventas Totales',
            'Costo Total',
            'Ganancia',
            'Margen',
        ];
    }

    public function map($detail): array
    {
        $margin = $detail->total_sales > 0 
            ? ($detail->total_profit / $detail->total_sales) * 100 
            : 0;

        return [
            $detail->product_name,
            $detail->identifier,
            match($detail->identifier_type) {
                'serial' => 'Serial',
                'imei' => 'IMEI',
                'code' => 'Código',
                default => $detail->identifier_type
            },
            number_format($detail->total_quantity, 0, ',', '.'),
            '$ ' . number_format($detail->total_sales, 0, ',', '.'),
            '$ ' . number_format($detail->total_cost, 0, ',', '.'),
            '$ ' . number_format($detail->total_profit, 0, ',', '.'),
            number_format($margin, 2, ',', '.') . '%',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '6366F1']], // Indigo
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
                'borders' => ['allBorders' => ['borderStyle' => 'thin']]
            ],
            
            // Alineaciones específicas
            'A' => ['alignment' => ['horizontal' => 'left']], // Producto
            'B:C' => ['alignment' => ['horizontal' => 'center']], // Identificadores
            'D' => ['alignment' => ['horizontal' => 'right']], // Cantidad
            'E:G' => ['alignment' => ['horizontal' => 'right']], // Valores monetarios
            'H' => ['alignment' => ['horizontal' => 'center']], // Margen

            // Formato condicional para stock
            'D2:D' . $lastRow => [
                'numberFormat' => ['formatCode' => '#,##0'],
                'conditionalStyles' => [
                    [
                        'conditionType' => 'cellValue',
                        'operator' => 'greaterThan',
                        'cellValue' => 100,
                        'style' => [
                            'font' => ['color' => ['rgb' => '047857']]
                        ]
                    ]
                ]
            ],

            // Bordes y formato general
            'A1:H' . $lastRow => [
                'borders' => ['allBorders' => ['borderStyle' => 'thin']],
                'font' => ['size' => 11]
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 40, // Producto
            'B' => 20, // Identificador
            'C' => 15, // Tipo ID
            'D' => 15, // Cantidad
            'E' => 20, // Ventas
            'F' => 20, // Costo
            'G' => 20, // Ganancia
            'H' => 15, // Margen
        ];
    }
}
