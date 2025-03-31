<?php

namespace App\Filament\Resources\DashboardResource\Pages;

use Filament\Resources\Pages\Page;
use App\Filament\Resources\DashboardResource;
use App\Filament\Widgets;

class Dashboard extends Page
{
    protected static string $resource = DashboardResource::class;
    protected static string $view = 'filament.resources.dashboard-resource.pages.dashboard';

    protected function getHeaderWidgets(): array
    {
        return [
            Widgets\SalesStatsOverview::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            Widgets\LowStockProducts::class,
            Widgets\TopProducts::class,
            Widgets\LatestSales::class,
        ];
    }
}
