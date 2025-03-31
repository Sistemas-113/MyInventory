<?php

namespace App\Filament\Resources\CreditResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CreditStatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = null;
    
    public $record;

    protected function getStats(): array
    {
        if (!$this->record) {
            return [];
        }

        // Calcular el total pagado sumando todos los pagos
        $totalPaid = $this->record->payments()->sum('amount');
        
        // Calcular el saldo pendiente
        $pendingBalance = $this->record->total_amount - $totalPaid;

        return [
            Stat::make('Total Crédito', '$ ' . number_format($this->record->total_amount, 0, ',', '.'))
                ->description('Valor Total')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('danger'),

            Stat::make('Total Pagado', '$ ' . number_format($totalPaid, 0, ',', '.'))
                ->description('Pagos Realizados')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Saldo Pendiente', '$ ' . number_format($pendingBalance, 0, ',', '.'))
                ->description($this->getPaymentProgress($totalPaid, $this->record->total_amount))
                ->descriptionIcon('heroicon-m-chart-bar')
                ->chart([
                    ($totalPaid / $this->record->total_amount) * 100,
                    ($pendingBalance / $this->record->total_amount) * 100
                ])
                ->color('warning'),

            Stat::make('Próximo Pago', '$ ' . number_format($this->record->next_payment_amount, 0, ',', '.'))
                ->description('Vence: ' . optional($this->record->next_payment_date)->format('d/m/Y'))
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),
        ];
    }

    protected function getPaymentProgress(float $paid, float $total): string
    {
        $percentage = ($paid / $total) * 100;
        return number_format($percentage, 1) . '% Completado';
    }
}
