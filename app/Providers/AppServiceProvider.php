<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Facades\Filament; // Importar correctamente la clase Filament

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Filament::registerResources([
            \App\Filament\Resources\PlatformResource::class,
        ]);
    }
}
