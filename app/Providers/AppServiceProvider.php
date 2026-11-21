<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\OpenWeatherService;
use App\Services\ClimaService;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(OpenWeatherService::class);
        $this->app->singleton(ClimaService::class);
    }

    public function boot(): void
    {
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