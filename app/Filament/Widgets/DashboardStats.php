<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Sale;
use App\Models\Client;

class DashboardStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Ventas del Día', '$ ' . number_format(
                Sale::whereDate('created_at', today())->sum('total_amount'), 
                0, ',', '.'
            ))
                ->description('Total ventas hoy')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make('Ventas Pendientes', Sale::where('status', 'pending')->count())
                ->description('Por completar')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Total Clientes', Client::count())
                ->description('Clientes registrados')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),
        ];
    }
}
