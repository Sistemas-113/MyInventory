<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class ProfitStats extends BaseWidget
{
    protected function getStats(): array
    {
        $dailyProfit = Sale::whereDate('created_at', today())
            ->with('details')
            ->get()
            ->sum(function ($sale) {
                return $sale->details->sum(function ($detail) {
                    return ($detail->unit_price - $detail->purchase_price) * $detail->quantity;
                });
            });

        $monthlyProfit = Sale::whereMonth('created_at', now()->month)
            ->with('details')
            ->get()
            ->sum(function ($sale) {
                return $sale->details->sum(function ($detail) {
                    return ($detail->unit_price - $detail->purchase_price) * $detail->quantity;
                });
            });

        $pendingCredits = Sale::where('payment_type', 'credit')
            ->where('status', 'pending')
            ->sum('total_amount');

        return [
            Stat::make('Ganancia del Día', '$ ' . number_format($dailyProfit, 0, ',', '.'))
                ->description('Basado en precio de compra vs venta')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('success'),

            Stat::make('Ganancia del Mes', '$ ' . number_format($monthlyProfit, 0, ',', '.'))
                ->description('Total del mes actual')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->chart([15, 4, 17, 7, 2, 10, 3])
                ->color('primary'),

            Stat::make('Créditos Pendientes', '$ ' . number_format($pendingCredits, 0, ',', '.'))
                ->description('Total por cobrar')
                ->descriptionIcon('heroicon-m-clock')
                ->chart([2, 10, 3, 15, 4, 17, 7])
                ->color('warning'),
        ];
    }
}
