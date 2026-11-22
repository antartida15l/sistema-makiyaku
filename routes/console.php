<?php

use App\Models\ClimaHistorial;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Tareas programadas
|--------------------------------------------------------------------------
| Laravel 12 solo lee este archivo (app/Console/Kernel.php ya no se usa).
|
| En local, para que corran sin configurar cron, deja abierta una terminal con:
|     php artisan schedule:work
|
| En un servidor real basta con una linea de cron:
|     * * * * * cd /ruta/del/proyecto && php artisan schedule:run >> /dev/null 2>&1
*/

// Clima de Open-Meteo y alertas, cada 30 minutos (las alertas siempre se
// recalculan justo despues de bajar el clima nuevo, asi no llegan desfasadas).
Schedule::command('parcelas:actualizar-clima')
    ->everyThirtyMinutes()
    ->name('clima-y-alertas')
    ->withoutOverlapping(20)
    ->onFailure(fn () => logger()->error('Fallo la actualizacion programada de clima y alertas'));

// Archivar alertas vencidas y borrar historial viejo.
Schedule::command('alertas:limpiar')
    ->hourlyAt(10)
    ->name('limpiar-alertas')
    ->withoutOverlapping();

// Geocodificar ubicaciones sin coordenadas (una vez por semana).
Schedule::command('ubicaciones:geocodificar')
    ->weeklyOn(0, '02:00')
    ->name('geocodificar-ubicaciones')
    ->withoutOverlapping();

// Mantener solo 30 dias de historial de clima.
Schedule::call(function () {
    $n = ClimaHistorial::where('fecha_registro', '<', now()->subDays(30))->delete();
    logger()->info("Historial de clima: {$n} registros antiguos eliminados");
})->dailyAt('03:30')->name('limpiar-clima');
