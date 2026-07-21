<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    // Public: rate-limited to blunt credential stuffing.
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:6,1');

    // Authenticated: 'tenant' binds the caller's company for scoped queries.
    Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });
});
