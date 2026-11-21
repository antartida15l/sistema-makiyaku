<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/**
 * ═══════════════════════════════════════════════════════
 * 💬 COMANDOS ARTISAN
 * ═══════════════════════════════════════════════════════
 */

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * ═══════════════════════════════════════════════════════
 * ⏰ TAREAS PROGRAMADAS (CRON JOBS)
 * ═══════════════════════════════════════════════════════
 */

// ✅ Actualizar clima de todas las parcelas activas todos los días a las 9 AM
Schedule::command('parcelas:actualizar-clima')
    ->dailyAt('09:00')
    ->timezone('America/Lima')
    ->name('Actualización diaria de clima')
    ->description('Actualiza el clima de todas las parcelas activas')
    ->onSuccess(function () {
        logger()->info('✅ Actualización de clima completada exitosamente');
    })
    ->onFailure(function () {
        logger()->error('❌ Falló la actualización de clima programada');
    });

// 🧹 OPCIONAL: Limpieza mensual de registros climáticos antiguos (mantener solo últimos 90 días)
Schedule::call(function () {
    $eliminados = \App\Models\ClimaHistorial::limpiarHistorialAntiguo(90);
    logger()->info("🧹 Limpieza de historial climático: {$eliminados} registros eliminados");
})
    ->monthlyOn(1, '03:00')
    ->timezone('America/Lima')
    ->name('Limpieza mensual de historial climático')
    ->description('Elimina registros climáticos mayores a 90 días');

/**
 * ═══════════════════════════════════════════════════════
 * 📝 NOTAS IMPORTANTES
 * ═══════════════════════════════════════════════════════
 * 
 * Para que estos comandos se ejecuten automáticamente, debes configurar
 * un CRON job en tu servidor con este comando:
 * 
 * * * * * * cd /ruta-de-tu-proyecto && php artisan schedule:run >> /dev/null 2>&1
 * 
 * En servidor local puedes probar manualmente con:
 * php artisan parcelas:actualizar-clima
 * 
 * Para ver todas las tareas programadas:
 * php artisan schedule:list
 * 
 * Para probar el schedule localmente:
 * php artisan schedule:work
 * ═══════════════════════════════════════════════════════
 */