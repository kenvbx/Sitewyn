<?php

use Illuminate\Support\Facades\Route;
use Sitewyn\Packages\Media\Http\Controllers\Admin\MediaController;
use Sitewyn\Packages\Media\Http\Controllers\Admin\MediaFileController;
use Sitewyn\Packages\Media\Http\Controllers\Admin\MediaFolderActionController;
use Sitewyn\Packages\Media\Http\Controllers\Admin\MediaFolderController;
use Sitewyn\Packages\Media\Http\Controllers\Admin\MediaPickerController;
use Sitewyn\Packages\Media\Http\Controllers\Admin\MediaUploadController;

Route::get('/_platform/package/media', static fn () => response()->json([
    'module' => 'package/media',
    'status' => 'ok',
]))->name('platform.package.media.health');

Route::prefix('admin')
    ->middleware(['web', 'auth:admin'])
    ->name('admin.')
    ->group(function (): void {
        Route::get('media', MediaController::class)
            ->name('media.index')
            ->middleware('permission:media.index');
        Route::get('media/picker', MediaPickerController::class)
            ->name('media.picker')
            ->middleware('permission:media.index');
        Route::post('media/folders', MediaFolderController::class)
            ->name('media.folders.store')
            ->middleware('permission:media.upload');
        Route::patch('media/folders/{folder}', [MediaFolderActionController::class, 'update'])
            ->name('media.folders.update')
            ->middleware('permission:media.edit');
        Route::delete('media/folders/{folder}', [MediaFolderActionController::class, 'destroy'])
            ->name('media.folders.destroy')
            ->middleware('permission:media.delete');
        Route::patch('media/files/{file}', [MediaFileController::class, 'update'])
            ->name('media.files.update')
            ->middleware('permission:media.edit');
        Route::delete('media/files/{file}', [MediaFileController::class, 'destroy'])
            ->name('media.files.destroy')
            ->middleware('permission:media.delete');
        Route::post('media/upload', MediaUploadController::class)
            ->name('media.upload')
            ->middleware('permission:media.upload');
    });
