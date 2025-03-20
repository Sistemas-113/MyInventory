<?php

namespace App\Filament\Resources\SaleResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SaleStatsOverview extends BaseWidget
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
                
            Stat::make('Total Venta', '$ ' . number_format($this->record->total_amount, 0, ',', '.'))
                ->description('Monto Total')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('primary'),
                
            Stat::make('Cuotas Pendientes', "{$this->record->remaining_installments} de {$this->record->installments}")
                ->description('Estado de Cuotas')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('warning'),
        ];
    }
}
