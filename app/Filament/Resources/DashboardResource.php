<?php

namespace App\Filament\Resources;

use Filament\Resources\Resource;
use App\Filament\Widgets\SalesStatsOverview;
use App\Filament\Widgets\LowStockProducts;
use App\Filament\Widgets\TopProducts;
use App\Filament\Widgets\LatestSales;
use App\Filament\Resources\DashboardResource\Pages\Dashboard;

class DashboardResource extends Resource
{
    public static function getWidgets(): array
    {
        return [
            SalesStatsOverview::class,
            LowStockProducts::class,
            TopProducts::class,
            LatestSales::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Dashboard::route('/'),
        ];
    }
}
