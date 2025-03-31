<?php

namespace App\Exports;

use App\Models\Installment;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InstallmentsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
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
        return Installment::query()
            ->with(['sale', 'sale.client', 'payments'])
            ->when($this->startDate, fn($q) => $q->whereDate('due_date', '>=', $this->startDate))
            ->when($this->endDate, fn($q) => $q->whereDate('due_date', '<=', $this->endDate))
            ->orderBy('due_date', 'desc');  // Ordenar por fecha de vencimiento descendente
    }

    public function headings(): array
    {
        return [
            'Cliente',
            'Teléfono',
            'N° Cuota',
            'Monto',
            'Vencimiento',
            'Estado',
            'Total Pagado',
            'Saldo',
            'Fecha Último Pago',
            'Método Pago',
            'Observaciones'
        ];
    }

    public function map($installment): array
    {
        $ultimoPago = $installment->payments->sortByDesc('payment_date')->first();

        return [
            $installment->sale->client->name,
            $installment->sale->client->phone,
            $installment->installment_number,
            '$ ' . number_format($installment->amount, 0, ',', '.'),
            $installment->due_date->format('d/m/Y'),
            match($installment->status) {
                'pending' => $installment->total_paid > 0 ? 'Abono Parcial' : 'Pendiente',
                'paid' => 'Pagada',
                'overdue' => 'Vencida',
                default => ucfirst($installment->status)
            },
            '$ ' . number_format($installment->total_paid, 0, ',', '.'),
            '$ ' . number_format($installment->remaining_amount, 0, ',', '.'),
            $ultimoPago ? $ultimoPago->payment_date->format('d/m/Y') : '-',
            $ultimoPago ? match($ultimoPago->payment_method) {
                'cash' => 'Efectivo',
                'transfer' => 'Transferencia',
                default => ucfirst($ultimoPago->payment_method)
            } : '-',
            $this->getObservaciones($installment)
        ];
    }

    private function getObservaciones($installment): string
    {
        $obs = [];

        if ($installment->status === 'paid') {
            $obs[] = "PAGADA: " . $installment->paid_date?->format('d/m/Y');
        } elseif ($installment->status === 'overdue') {
            $obs[] = "VENCIDA: {$installment->due_date->diffInDays(now())} días";
        }

        if ($installment->total_paid > 0 && $installment->remaining_amount > 0) {
            $obs[] = "ABONADO: " . number_format(
                ($installment->total_paid / $installment->amount) * 100, 
                1
            ) . "%";
        }

        return implode(' | ', $obs);
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'DC2626']], // Rojo para vencidas
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
                'borders' => ['allBorders' => ['borderStyle' => 'thin']]
            ],
            
            // Alineaciones específicas
            'A' => ['alignment' => ['horizontal' => 'left']], // Cliente
            'B' => ['alignment' => ['horizontal' => 'center']], // Teléfono
            'C' => ['alignment' => ['horizontal' => 'center']], // N° Cuota
            'D:H' => ['alignment' => ['horizontal' => 'right']], // Montos
            'I' => ['alignment' => ['horizontal' => 'center']], // Días Atraso
            'J' => ['alignment' => ['horizontal' => 'center']], // Última Fecha
            'K' => ['alignment' => ['horizontal' => 'left']], // Observaciones

            // Resaltar días de atraso
            'I2:I' . $lastRow => [
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

            // Bordes y formato general
            'A1:K' . $lastRow => [
                'borders' => ['allBorders' => ['borderStyle' => 'thin']],
                'font' => ['size' => 11]
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30, // Cliente
            'B' => 15, // Teléfono
            'C' => 10, // N° Cuota
            'D' => 20, // Monto
            'E' => 15, // Vencimiento
            'F' => 15, // Estado
            'G' => 20, // Total Pagado
            'H' => 20, // Saldo
            'I' => 15, // Fecha Último Pago
            'J' => 15, // Método Pago
            'K' => 40, // Observaciones
        ];
    }
}
