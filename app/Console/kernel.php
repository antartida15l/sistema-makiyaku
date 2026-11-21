<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define los comandos Artisan de la aplicación.
     *
     * @var array
     */
    protected $commands = [
        Commands\ActualizarClimaParcelasCommand::class,
        Commands\GeocodificarUbicaciones::class,
    ];

    /**
     * Define el calendario de tareas programadas de la aplicación.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //  ACTUALIZAR CLIMA DIARIAMENTE A LAS 6:00 AM
        $schedule->command('parcelas:actualizar-clima --auto')
                 ->dailyAt('06:00')
                 ->withoutOverlapping()    // Evita ejecuciones simultáneas
                 ->runInBackground()       // Se ejecuta en segundo plano
                 ->onSuccess(function () {
                     info('Clima actualizado exitosamente');
                 })
                 ->onFailure(function () {
                     logger()->error(' Error al actualizar clima');
                 });

        // Geocodificar ubicaciones sin coordenadas (semanal)
        $schedule->command('ubicaciones:geocodificar')
                 ->weekly()
                 ->sundays()
                 ->at('02:00')
                 ->withoutOverlapping();
    }

    /**
     * Registra los comandos para la aplicación.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}