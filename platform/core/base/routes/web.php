<?php

use Illuminate\Support\Facades\Route;
use Sitewyn\Core\Base\Http\Controllers\Admin\AuthController;
use Sitewyn\Core\Base\Http\Controllers\Admin\DashboardController;
use Sitewyn\Core\Base\Http\Controllers\Admin\PasswordResetController;
use Sitewyn\Core\Base\Http\Controllers\Admin\PermissionController;
use Sitewyn\Core\Base\Http\Controllers\Admin\PluginManageController;
use Sitewyn\Core\Base\Http\Controllers\Admin\RoleController;
use Sitewyn\Core\Base\Http\Controllers\Admin\SettingController;
use Sitewyn\Core\Base\Http\Controllers\Admin\UserController;

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

            Route::get('roles', [RoleController::class, 'index'])
                ->middleware('permission:roles.index')
                ->name('roles.index');
            Route::get('roles/create', [RoleController::class, 'create'])
                ->middleware('permission:roles.create')
                ->name('roles.create');
            Route::post('roles', [RoleController::class, 'store'])
                ->middleware('permission:roles.create')
                ->name('roles.store');
            Route::get('roles/{role}/edit', [RoleController::class, 'edit'])
                ->middleware('permission:roles.edit')
                ->name('roles.edit');
            Route::put('roles/{role}', [RoleController::class, 'update'])
                ->middleware('permission:roles.edit')
                ->name('roles.update');
            Route::delete('roles/{role}', [RoleController::class, 'destroy'])
                ->middleware('permission:roles.delete')
                ->name('roles.destroy');

            Route::get('users', [UserController::class, 'index'])
                ->middleware('permission:users.index')
                ->name('users.index');
            Route::get('users/create', [UserController::class, 'create'])
                ->middleware('permission:users.create')
                ->name('users.create');
            Route::post('users', [UserController::class, 'store'])
                ->middleware('permission:users.create')
                ->name('users.store');
            Route::get('users/{user}/edit', [UserController::class, 'edit'])
                ->middleware('permission:users.edit')
                ->name('users.edit');
            Route::put('users/{user}', [UserController::class, 'update'])
                ->middleware('permission:users.edit')
                ->name('users.update');
            Route::delete('users/{user}', [UserController::class, 'destroy'])
                ->middleware('permission:users.delete')
                ->name('users.destroy');

            Route::get('permissions', [PermissionController::class, 'index'])
                ->middleware('permission:permissions.index')
                ->name('permissions.index');

            Route::get('settings', [SettingController::class, 'edit'])
                ->middleware('permission:settings.edit')
                ->name('settings.edit');
            Route::put('settings', [SettingController::class, 'update'])
                ->middleware('permission:settings.edit')
                ->name('settings.update');

            Route::get('plugins', [PluginManageController::class, 'index'])
                ->middleware('permission:plugins.manage')
                ->name('plugins.index');
            Route::post('plugins/{slug}/activate', [PluginManageController::class, 'activate'])
                ->middleware('permission:plugins.manage')
                ->name('plugins.activate');
            Route::post('plugins/{slug}/deactivate', [PluginManageController::class, 'deactivate'])
                ->middleware('permission:plugins.manage')
                ->name('plugins.deactivate');
        });
    });
