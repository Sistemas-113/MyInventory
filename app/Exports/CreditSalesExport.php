<?php

namespace App\Exports;

use App\Models\Sale;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CreditSalesExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
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
            ->with([
                'client', 
                'details',
                'installments' => function($query) {
                    $query->orderBy('installment_number');
                },
                'payments' => function($query) {
                    $query->orderBy('payment_date', 'desc');
                }
            ])
            ->where('payment_type', 'credit')
            ->where('status', '!=', 'completed')  // Excluir créditos completados
            ->whereHas('installments', function($query) {
                $query->where('status', 'pending');  // Solo créditos con cuotas pendientes
            })
            ->when($this->startDate, fn($q) => $q->whereDate('created_at', '>=', $this->startDate))
            ->when($this->endDate, fn($q) => $q->whereDate('created_at', '<=', $this->endDate));
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Cliente',
            'Teléfono',
            'Valor Crédito',
            'Cuota Inicial',
            'Tasa Interés',
            'Cuotas Totales',
            'Cuotas Pagadas',
            'Cuotas Pendientes',
            'Cuotas Vencidas',
            'Total Abonado',
            'Saldo Pendiente',
            'Estado',
            'Último Pago',
            'Próximo Vencimiento'
        ];
    }

    public function map($sale): array
    {
        $installments = $sale->installments()->get();
        $payments = $sale->payments()->get();
        
        $cuotasPagadas = $installments->where('status', 'paid')->count();
        $cuotasPendientes = $installments->where('status', 'pending')->count();
        $cuotasVencidas = $installments->where('status', 'overdue')->count();
        $totalAbonado = $payments->sum('amount');
        $saldoPendiente = $sale->total_amount - $totalAbonado;
        
        $ultimoPago = $payments->sortByDesc('payment_date')->first();
        $proximoVencimiento = $installments
            ->filter(fn($i) => $i->status !== 'paid')
            ->sortBy('due_date')
            ->first()?->due_date;

        return [
            $sale->created_at->format('d/m/Y'),
            $sale->client->name,
            $sale->client->phone,
            '$ ' . number_format($sale->total_amount, 0, ',', '.'),
            '$ ' . number_format($sale->initial_payment ?? 0, 0, ',', '.'),
            $sale->interest_rate . '%',
            $sale->installments ?? 0,
            $cuotasPagadas,
            $cuotasPendientes,
            $cuotasVencidas,
            '$ ' . number_format($totalAbonado, 0, ',', '.'),
            '$ ' . number_format($saldoPendiente, 0, ',', '.'),
            $this->getEstadoCredito($sale, $installments),
            $ultimoPago ? $ultimoPago->payment_date->format('d/m/Y') : '-',
            $proximoVencimiento ? $proximoVencimiento->format('d/m/Y') : '-',
        ];
    }

    private function getEstadoCredito($sale, $installments): string
    {
        if ($installments->where('status', 'overdue')->count() > 0) {
            return 'EN MORA';
        }
        
        if ($sale->payments->sum('amount') >= $sale->total_amount) {
            return 'COMPLETADO';
        }
        
        if ($sale->payments->sum('amount') > 0) {
            return 'AL DÍA';
        }
        
        return 'PENDIENTE';
    }

    public function styles(Worksheet $sheet)
    {
        // Obtener la última fila
        $lastRow = $sheet->getHighestRow();
        
        return [
            // Estilo del encabezado
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '004d99']],
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
                'borders' => ['allBorders' => ['borderStyle' => 'thin']]
            ],
            // Alineaciones por columna
            'A' => ['alignment' => ['horizontal' => 'center']], // Fecha
            'B' => ['alignment' => ['horizontal' => 'left']], // Cliente
            'C' => ['alignment' => ['horizontal' => 'center']], // Teléfono
            'D:L' => ['alignment' => ['horizontal' => 'right']], // Montos
            'M' => ['alignment' => ['horizontal' => 'center']], // Estado
            'N:O' => ['alignment' => ['horizontal' => 'center']], // Fechas
            
            // Formato condicional para estados
            'M2:M' . $lastRow => [
                'conditionalStyles' => [
                    [
                        'conditionType' => 'containsText',
                        'containsText' => 'EN MORA',
                        'style' => [
                            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'FFE0E0']],
                            'font' => ['color' => ['rgb' => 'CC0000']]
                        ]
                    ],
                    [
                        'conditionType' => 'containsText',
                        'containsText' => 'AL DÍA',
                        'style' => [
                            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E0FFE0']],
                            'font' => ['color' => ['rgb' => '006100']]
                        ]
                    ]
                ]
            ],
            
            // Bordes y formato para todas las celdas
            'A1:O' . $lastRow => [
                'borders' => ['allBorders' => ['borderStyle' => 'thin']],
                'font' => ['size' => 11]
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,  // Fecha
            'B' => 30,  // Cliente
            'C' => 15,  // Teléfono
            'D' => 20,  // Valor Crédito
            'E' => 20,  // Cuota Inicial
            'F' => 15,  // Tasa Interés
            'G' => 15,  // Cuotas Totales
            'H' => 15,  // Cuotas Pagadas
            'I' => 15,  // Cuotas Pendientes
            'J' => 15,  // Cuotas Vencidas
            'K' => 20,  // Total Abonado
            'L' => 20,  // Saldo Pendiente
            'M' => 20,  // Estado
            'N' => 15,  // Último Pago
            'O' => 15,  // Próximo Vencimiento
        ];
    }
}
