<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesTracking extends BaseWidget
{
    protected function getStats(): array
    {
        // Ventas del mes actual
        $ventasMes = Sale::whereMonth('created_at', now()->month);
        $totalVentasMes = $ventasMes->sum('total_amount');
        
        // Calcular ganancias
        $gananciaMes = $ventasMes->with('details')->get()->sum(function ($sale) {
            return $sale->details->sum(function ($detail) {
                return ($detail->unit_price - $detail->purchase_price) * $detail->quantity;
            });
        });

        // Créditos pendientes
        $creditosPendientes = Sale::where('payment_type', 'credit')
            ->where('status', 'pending')
            ->withCount(['installments as total_installments'])
            ->withCount(['installments as pending_installments' => function ($query) {
                $query->where('status', 'pending');
            }])
            ->get();

        $montoPendiente = $creditosPendientes->sum(function ($credito) {
            return $credito->installments()->where('status', 'pending')->sum('amount');
        });

        // Pagos vencidos
        $pagosVencidos = $creditosPendientes->sum(function ($credito) {
            return $credito->installments()
                ->where('status', 'pending')
                ->where('due_date', '<', now())
                ->count();
        });

        return [
            Stat::make('Ventas del Mes', '$ ' . number_format($totalVentasMes, 0, ',', '.'))
                ->description('Total vendido este mes')
                ->color('primary'),

            Stat::make('Ganancia Mes', '$ ' . number_format($gananciaMes, 0, ',', '.'))
                ->description('Ganancia bruta del mes')
                ->color('success'),

            Stat::make('Créditos Pendientes', number_format($creditosPendientes->count(), 0))
                ->description('Total: $ ' . number_format($montoPendiente, 0, ',', '.'))
                ->color('warning'),

            Stat::make('Pagos Vencidos', number_format($pagosVencidos, 0))
                ->description('Cuotas atrasadas')
                ->color('danger'),
        ];
    }
}
