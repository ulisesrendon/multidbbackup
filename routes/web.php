<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DatabaseConnectionController;
use Illuminate\Support\Facades\Route;

// ─── First-time setup ────────────────────────────────────────────────────────
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);

// ─── Authentication ───────────────────────────────────────────────────────────
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ─── Protected dashboard ─────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Database connections
    Route::post('/connections', [DatabaseConnectionController::class, 'store'])
        ->name('connections.store');
    Route::put('/connections/{connection}/toggle', [DatabaseConnectionController::class, 'toggle'])
        ->name('connections.toggle');
    Route::put('/connections/{connection}/schedules', [DatabaseConnectionController::class, 'updateSchedules'])
        ->name('connections.schedules.update');
    Route::delete('/connections/{connection}', [DatabaseConnectionController::class, 'destroy'])
        ->name('connections.destroy');

    // Manual backup trigger
    Route::post('/connections/{connection}/backup', [BackupController::class, 'runNow'])
        ->name('connections.backup');
});

