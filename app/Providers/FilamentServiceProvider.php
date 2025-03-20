<?php

namespace App\Providers;

use Filament\Facades\Filament;
use Illuminate\Support\ServiceProvider;
use App\Models\Platform;

class FilamentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Filament::serving(function () {
            $platform = Platform::first();
            if ($platform) {
                Filament::registerNavigationGroups([
                    'Ventas',
                    'Configuración',
                ]);

                Filament::registerNavigationItems([
                    Filament::makeNavigationItem()
                        ->label($platform->name)
                        ->icon('heroicon-o-cog') // Usar un icono válido
                        ->url('/')
                        ->group('Configuración'),
                ]);
            }
        });
    }
}
