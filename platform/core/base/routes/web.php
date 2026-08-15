<?php

use Illuminate\Support\Facades\Route;
use Sitewyn\Core\Base\Http\Controllers\Admin\AuthController;
use Sitewyn\Core\Base\Http\Controllers\Admin\DashboardController;
use Sitewyn\Core\Base\Http\Controllers\Admin\PasswordResetController;

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
            Route::get('forgot-password', [PasswordResetController::class, 'showForgotPasswordForm'])->name('password.request');
            Route::post('forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
            Route::get('reset-password/{token}', [PasswordResetController::class, 'showResetPasswordForm'])->name('password.reset');
            Route::post('reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.update');
        });

        Route::middleware('auth:admin')->group(function (): void {
            Route::get('/', DashboardController::class)->name('dashboard');
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        });
    });
