<?php

use App\Http\Controllers\AlertaController;
use App\Http\Controllers\ClimaController;
use App\Http\Controllers\CultivoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ParcelaController;
use App\Http\Controllers\PreferenciaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecomendacionController;
use App\Http\Controllers\ReporteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Publico
|--------------------------------------------------------------------------
*/
Route::get('/', \App\Http\Controllers\InicioController::class)->name('inicio');

/*
|--------------------------------------------------------------------------
| Aplicacion (requiere sesion)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

    // Panel
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/pronostico', [DashboardController::class, 'pronostico'])->name('dashboard.pronostico');

    // Presentacion animada y preferencias del usuario
    Route::post('/preferencias', [PreferenciaController::class, 'guardar'])->name('preferencias.guardar');

    // Parcelas
    Route::prefix('parcelas')->name('parcelas.')->group(function () {
        Route::get('/', [ParcelaController::class, 'index'])->name('index');
        Route::get('/create', [ParcelaController::class, 'create'])->name('create');
        Route::post('/', [ParcelaController::class, 'store'])->name('store');

        // Papelera (antes de /{parcela} para que no se confunda con un id)
        Route::get('/papelera/ver', [ParcelaController::class, 'trashed'])->name('trashed');
        Route::patch('/{id}/restaurar', [ParcelaController::class, 'restore'])->name('restore');
        Route::delete('/{id}/forzar-eliminacion', [ParcelaController::class, 'forceDestroy'])->name('forceDestroy');

        Route::get('/{parcela}', [ParcelaController::class, 'show'])->name('show');
        Route::get('/{parcela}/edit', [ParcelaController::class, 'edit'])->name('edit');
        Route::put('/{parcela}', [ParcelaController::class, 'update'])->name('update');
        Route::delete('/{parcela}', [ParcelaController::class, 'destroy'])->name('destroy');

        Route::post('/{parcela}/riego', [ParcelaController::class, 'registrarRiego'])->name('registrar-riego');
        Route::post('/{parcela}/actualizar-clima', [ParcelaController::class, 'actualizarClima'])->name('actualizar-clima');

        // Recomendaciones de una parcela
        Route::get('/{parcela}/recomendaciones', [RecomendacionController::class, 'index'])->name('recomendaciones');
    });

    // Geocodificacion para el formulario de parcelas
    Route::post('/api/geocodificar', [ParcelaController::class, 'geocodificarAjax'])->name('api.geocodificar');

    // Cultivos
    Route::resource('cultivos', CultivoController::class);

    // Clima
    Route::prefix('clima')->name('clima.')->group(function () {
        Route::get('/', [ClimaController::class, 'index'])->name('index');
        Route::get('/buscar-ciudades', [ClimaController::class, 'buscarCiudades'])->name('buscar');
        Route::get('/datos', [ClimaController::class, 'datos'])->name('datos');
    });

    // Alertas
    Route::prefix('alertas')->name('alertas.')->group(function () {
        Route::get('/', [AlertaController::class, 'index'])->name('index');
        Route::get('/recientes', [AlertaController::class, 'obtenerRecientes'])->name('recientes');
        Route::get('/contador', [AlertaController::class, 'contador'])->name('contador');
        Route::get('/archivadas', [AlertaController::class, 'archivadas'])->name('archivadas');
        Route::post('/refrescar', [AlertaController::class, 'refrescar'])->name('refrescar');
        Route::post('/marcar-todas-leidas', [AlertaController::class, 'marcarTodasLeidas'])->name('marcar-todas-leidas');
        Route::post('/{alerta}/marcar-leida', [AlertaController::class, 'marcarLeida'])->name('marcar-leida');
    });

    // Recomendaciones
    Route::get('/recomendaciones', [RecomendacionController::class, 'todas'])->name('recomendaciones.todas');
    Route::post('/recomendaciones/regenerar', [RecomendacionController::class, 'regenerarTodas'])->name('recomendaciones.regenerar-todas');
    Route::post('/parcelas/{parcela}/recomendaciones/regenerar', [RecomendacionController::class, 'regenerar'])->name('recomendaciones.regenerar');
    Route::post('/recomendaciones/{recomendacion}/aplicar', [RecomendacionController::class, 'aplicar'])->name('recomendaciones.aplicar');
    Route::post('/recomendaciones/{recomendacion}/descartar', [RecomendacionController::class, 'descartar'])->name('recomendaciones.descartar');

    // Reportes
    Route::prefix('reportes')->name('reportes.')->group(function () {
        Route::get('/', [ReporteController::class, 'index'])->name('index');
        Route::get('/ver', [ReporteController::class, 'ver'])->name('ver');
        Route::get('/csv', [ReporteController::class, 'csv'])->name('csv');
    });

    // Perfil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
