<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class SalesAnalytics extends BaseWidget
{
    protected function getStats(): array
    {
        // Calcular ganancia total (Precio venta - Precio compra)
        $profitDetails = DB::table('sale_details')
            ->select(
                DB::raw('SUM((unit_price * quantity) - (purchase_price * quantity)) as total_profit'),
                DB::raw('SUM(purchase_price * quantity) as total_cost'),
                DB::raw('SUM(unit_price * quantity) as total_sales'),
                DB::raw('COUNT(DISTINCT sale_id) as total_transactions')
            )
            ->first();

        // Calcular montos pendientes en créditos
        $creditStats = Sale::where('payment_type', 'credit')
            ->where('status', 'pending')
            ->select(
                DB::raw('COUNT(*) as total_credits'),
                DB::raw('SUM(total_amount) as total_credit_amount'),
                DB::raw('SUM(initial_payment) as total_initial_payments')
            )
            ->first();

        // Calcular margen de ganancia promedio
        $profitMargin = $profitDetails->total_sales > 0 
            ? ($profitDetails->total_profit / $profitDetails->total_sales) * 100 
            : 0;

        return [
            Stat::make('Ganancia Total', '$ ' . number_format($profitDetails->total_profit, 0, ',', '.'))
                ->description('Basado en precio de compra vs venta')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('success'),

            Stat::make('Margen de Ganancia', number_format($profitMargin, 1) . '%')
                ->description($profitDetails->total_transactions . ' transacciones')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),

            Stat::make('Inversión en Productos', '$ ' . number_format($profitDetails->total_cost, 0, ',', '.'))
                ->description('Costo total de productos')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('warning'),

            Stat::make('Créditos Pendientes', '$ ' . number_format($creditStats->total_credit_amount ?? 0, 0, ',', '.'))
                ->description($creditStats->total_credits . ' créditos activos')
                ->descriptionIcon('heroicon-m-clock')
                ->color('danger'),
        ];
    }
}
