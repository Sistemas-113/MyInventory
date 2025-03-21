<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Sale;
use App\Models\Client;

class SalesStatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Ventas', '$ ' . number_format(Sale::sum('total_amount'), 0, ',', '.'))
                ->description('Monto total de ventas')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('primary'),

            Stat::make('Ventas Pendientes', Sale::where('status', 'pending')->count())
                ->description('Ventas aún no completadas')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Clientes Registrados', Client::count())
                ->description('Total de clientes')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('Ganancia Total', '$ ' . number_format(Sale::all()->sum(function ($sale) {
                    return $sale->details->sum(function ($detail) {
                        return ($detail->unit_price - $detail->purchase_price) * $detail->quantity;
                    });
                }), 0, ',', '.'))
                ->description('Ganancia total de ventas')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('success'),
        ];
    }
}
