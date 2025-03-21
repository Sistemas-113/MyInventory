<?php

namespace App\Filament\Resources\DashboardResource\Pages;

use Filament\Resources\Pages\Page;
use App\Filament\Resources\DashboardResource;

class Dashboard extends Page
{
    protected static string $resource = DashboardResource::class;

    protected static string $view = 'filament.resources.dashboard-resource.pages.dashboard';
}
