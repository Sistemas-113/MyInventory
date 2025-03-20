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

        return [
            Stat::make('Cuota Inicial', '$ ' . number_format($this->record->initial_payment, 0, ',', '.'))
                ->description('Pago Inicial')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
                
            Stat::make('Monto Total', '$ ' . number_format($this->record->total_amount, 0, ',', '.'))
                ->description('Deuda Total')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('danger'),
                
            Stat::make('Cuotas Pendientes', "{$this->record->remaining_installments} de {$this->record->installments}")
                ->description('Próximo pago: $ ' . number_format($this->record->next_payment_amount, 0, ',', '.'))
                ->descriptionIcon('heroicon-m-calendar')
                ->chart([3, 2, 1])
                ->color('warning'),

            Stat::make('Interés', "{$this->record->interest_rate}%")
                ->description('Tasa de Interés')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),
        ];
    }
}
