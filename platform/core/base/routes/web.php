<?php

use Illuminate\Support\Facades\Route;
use Sitewyn\Core\Base\Http\Controllers\Admin\AuthController;
use Sitewyn\Core\Base\Http\Controllers\Admin\DashboardController;

Route::get('/_platform/core/base', static fn () => response()->json([
    'module' => 'core/base',
    'status' => 'ok',
]))->name('platform.core.base.health');

Route::prefix('admin')
    ->middleware('web')
    ->name('admin.')
    ->group(function (): void {
        Route::middleware('guest:admin')->group(function (): void {
            Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
            Route::post('login', [AuthController::class, 'login'])->name('login.store');
        });

        Route::middleware('auth:admin')->group(function (): void {
            Route::get('/', DashboardController::class)->name('dashboard');
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        });
    });
