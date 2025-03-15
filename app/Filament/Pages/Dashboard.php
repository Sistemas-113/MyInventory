<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\{
    StatsOverview,
    TopProducts,
    LowStockProducts,
    LatestSales
};
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getColumns(): int | array
    {
        return [
            'md' => 2,
            'xl' => 3,
        ];
    }

    public function getWidgets(): array  // Cambiado a public
    {
        return [
            // Estadísticas generales
            StatsOverview::class,
            
            // Widgets de datos
            TopProducts::class,
            LatestSales::class,
            LowStockProducts::class,
        ];
    }
}
