<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\AlertaService;
use App\Services\ClimaService;
use App\Services\MonitoreoService;
use App\Services\OpenMeteoService;
use App\Services\RiegoService;
use Illuminate\Support\Facades\URL;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(OpenMeteoService::class);
        $this->app->singleton(RiegoService::class);
        $this->app->singleton(ClimaService::class);
        $this->app->singleton(AlertaService::class);
        $this->app->singleton(MonitoreoService::class);
    }

    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.mk');
        Paginator::defaultSimpleView('vendor.pagination.mk');

        // Contador de alertas del menu lateral (una consulta por pagina)
        View::composer('layouts.app', function ($vista) {
            $vista->with('alertasActivas', auth()->check() ? \App\Models\Alerta::contarActivasPorUsuario(auth()->id()) : 0);
        });

        {
            if (config('app.env') !== 'local') {
                URL::forceScheme('https');
            }
            
            // Si usas ngrok o VS Code, esto ayuda a que los assets y links se generen bien
            if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
                URL::forceScheme('https');
            }
        }
    }

    
}