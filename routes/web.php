<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ParcelaController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Rutas protegidas que requieren autenticación
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Parcelas - Resource Controller (genera todas las rutas CRUD)
    Route::resource('parcelas', ParcelaController::class);
    
    // O si prefieres definir las rutas manualmente:
    /*
    Route::get('/parcelas', [ParcelaController::class, 'index'])->name('parcelas.index');
    Route::get('/parcelas/create', [ParcelaController::class, 'create'])->name('parcelas.create');
    Route::post('/parcelas', [ParcelaController::class, 'store'])->name('parcelas.store');
    Route::get('/parcelas/{parcela}', [ParcelaController::class, 'show'])->name('parcelas.show');
    Route::get('/parcelas/{parcela}/edit', [ParcelaController::class, 'edit'])->name('parcelas.edit');
    Route::put('/parcelas/{parcela}', [ParcelaController::class, 'update'])->name('parcelas.update');
    Route::delete('/parcelas/{parcela}', [ParcelaController::class, 'destroy'])->name('parcelas.destroy');
    */
    
    // Perfil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';