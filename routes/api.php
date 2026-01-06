<?php

use App\Http\Controllers\modules\Auth\AuthController;
use App\Http\Controllers\RobotController;
use Illuminate\Support\Facades\Route;

// Rotas públicas
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

// Rotas protegidas
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });

    // Rotas de Robôs
    Route::apiResource('robots', RobotController::class);
});

