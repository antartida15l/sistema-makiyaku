<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ParcelaController;
use App\Http\Controllers\CultivoController;
use App\Http\Controllers\ClimaController;
use App\Http\Controllers\AlertaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecomendacionController;

/*
|--------------------------------------------------------------------------
| Ruta pública de bienvenida
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Rutas protegidas (requieren autenticación y verificación)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // Datos AJAX del dashboard
    Route::get('/dashboard/humedad-data', [DashboardController::class, 'getHumedadData'])
        ->name('dashboard.humedad-data');

    Route::get('/dashboard/comparativa-clima', [DashboardController::class, 'getComparativaClima'])
        ->name('dashboard.comparativa');

    Route::get('/dashboard/pronostico', [DashboardController::class, 'getPronostico'])
        ->name('dashboard.pronostico');

        /*
        |----------------------------------------------------------------------
        | RECOMENDACIONES INTELIGENTES POR PARCELA
        |----------------------------------------------------------------------
        */
        Route::prefix('parcelas/{parcela}/recomendaciones')
            ->name('recomendaciones.')
            ->group(function () {

                // Listar recomendaciones de una parcela
                Route::get('/', [RecomendacionController::class, 'index'])
                    ->name('index');

                // Regenerar recomendaciones
                Route::post('/regenerar', [RecomendacionController::class, 'regenerar'])
                    ->name('regenerar');
            });

        // Acciones individuales sobre recomendaciones
        Route::post('/recomendaciones/{recomendacion}/aplicar',
            [RecomendacionController::class, 'aplicar'])
            ->name('recomendaciones.aplicar');

        Route::post('/recomendaciones/{recomendacion}/descartar',
            [RecomendacionController::class, 'descartar'])
            ->name('recomendaciones.descartar');


    /*
    |--------------------------------------------------------------------------
    | PARCELAS – CRUD + FUNCIONALIDADES
    |--------------------------------------------------------------------------
    */
    Route::prefix('parcelas')->name('parcelas.')->group(function () {

        // CRUD básico
        Route::get('/', [ParcelaController::class, 'index'])->name('index');
        Route::get('/create', [ParcelaController::class, 'create'])->name('create');
        Route::post('/', [ParcelaController::class, 'store'])->name('store');
        Route::get('/{parcela}', [ParcelaController::class, 'show'])->name('show');
        Route::get('/{parcela}/edit', [ParcelaController::class, 'edit'])->name('edit');
        Route::put('/{parcela}', [ParcelaController::class, 'update'])->name('update');
        Route::delete('/{parcela}', [ParcelaController::class, 'destroy'])->name('destroy');

        // Soft Deletes
        Route::get('/papelera/ver', [ParcelaController::class, 'trashed'])->name('trashed');
        Route::patch('/{id}/restaurar', [ParcelaController::class, 'restore'])->name('restore');
        Route::delete('/{id}/forzar-eliminacion', [ParcelaController::class, 'forceDestroy'])
            ->name('forceDestroy');

        // Registro de riego
        Route::post('/{parcela}/riego', [ParcelaController::class, 'registrarRiego'])
            ->name('registrar-riego');

        // Actualizar clima manualmente
        Route::post('/{parcela}/actualizar-clima', [ParcelaController::class, 'actualizarClima'])
            ->name('actualizar-clima');
    });

    /*
    |--------------------------------------------------------------------------
    | CULTIVOS – CRUD COMPLETO
    |--------------------------------------------------------------------------
    */
    Route::resource('cultivos', CultivoController::class);

    /*
    |--------------------------------------------------------------------------
    | CLIMA – CONSULTAS Y AJAX
    |--------------------------------------------------------------------------
    */
    Route::prefix('clima')->name('clima.')->group(function () {

        Route::get('/', [ClimaController::class, 'index'])->name('index');

        Route::get('/buscar-ciudades', [ClimaController::class, 'buscarCiudades'])
            ->name('buscar');

        Route::get('/coordenadas', [ClimaController::class, 'climaPorCoordenadas'])
            ->name('coordenadas');

        Route::post('/refrescar', [ClimaController::class, 'refrescarClima'])
            ->name('refrescar');

        Route::get('/pronostico', [ClimaController::class, 'pronosticoExtendido'])
            ->name('pronostico');

        Route::get('/resumen-lluvia', [ClimaController::class, 'resumenLluvia'])
            ->name('resumen-lluvia');
    });

    /*
    |--------------------------------------------------------------------------
    | API AUXILIAR – GEOCODIFICACIÓN
    |--------------------------------------------------------------------------
    */
    Route::post('/api/geocodificar', [ParcelaController::class, 'geocodificarAjax'])
        ->name('api.geocodificar');

    /*
    |--------------------------------------------------------------------------
    | ALERTAS – SISTEMA COMPLETO
    |--------------------------------------------------------------------------
    */
    Route::prefix('alertas')->name('alertas.')->group(function () {

        Route::get('/', [AlertaController::class, 'index'])->name('index');

        Route::get('/recientes', [AlertaController::class, 'obtenerRecientes'])
            ->name('recientes');

        Route::get('/contador', [AlertaController::class, 'contador'])
            ->name('contador');

        Route::post('/{alerta}/marcar-leida', [AlertaController::class, 'marcarLeida'])
            ->name('marcar-leida');

        Route::post('/marcar-todas-leidas', [AlertaController::class, 'marcarTodasLeidas'])
            ->name('marcar-todas-leidas');

        Route::post('/refrescar', [AlertaController::class, 'refrescar'])
            ->name('refrescar');

        Route::get('/archivadas', [AlertaController::class, 'archivadas'])
            ->name('archivadas');
    });

    /*
    |--------------------------------------------------------------------------
    | PERFIL DE USUARIO
    |--------------------------------------------------------------------------
    */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

});

/*
|--------------------------------------------------------------------------
| Rutas de autenticación (Laravel Breeze / Jetstream)
|--------------------------------------------------------------------------
*/
require __DIR__ . '/auth.php';
