<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CompanyController;
use Illuminate\Support\Facades\Route;

// Public tenant onboarding. Rate-limited to blunt automated abuse; in production
// this may later be gated behind a super-admin or an email-verified signup flow.
Route::post('companies', [CompanyController::class, 'store'])->middleware('throttle:10,1');

Route::prefix('auth')->group(function () {
    // Public: rate-limited to blunt credential stuffing.
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:6,1');

    // Authenticated: 'tenant' binds the caller's company for scoped queries.
    Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });
});
