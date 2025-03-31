<?php

namespace App\Providers;

use Filament\Facades\Filament;
use Illuminate\Support\ServiceProvider;

class FilamentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Filament::serving(function () {
            Filament::registerNavigationGroups([
                'Ventas',
                'Configuración',
            ]);

            Filament::registerResources([
                \App\Filament\Resources\SaleResource::class,
                \App\Filament\Resources\ClientResource::class,
                \App\Filament\Resources\CreditResource::class,
            ]);
        });
    }
}
