<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if(config('app.env') !== 'local') {
            URL::forceScheme('https');
        }

        // Agregar esto para regenerar el token CSRF
        if(request()->is('admin/*')) {
            config(['session.lifetime' => 480]); // 8 horas
            config(['session.expire_on_close' => false]);
        }
    }
}
