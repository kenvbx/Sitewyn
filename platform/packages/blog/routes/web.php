<?php

use Illuminate\Support\Facades\Route;
use Sitewyn\Packages\Blog\Http\Controllers\Admin\CategoryController;
use Sitewyn\Packages\Blog\Http\Controllers\Admin\PostController;
use Sitewyn\Packages\Blog\Http\Controllers\Admin\TagController;
use Sitewyn\Packages\Blog\Http\Controllers\Public\PostPublicController;

Route::get('/_platform/package/blog', static fn () => response()->json([
    'module' => 'package/blog',
    'status' => 'ok',
]))->name('platform.package.blog.health');

Route::prefix('admin')
    ->middleware(['web', 'auth:admin'])
    ->name('admin.')
    ->group(function (): void {
        Route::get('posts', [PostController::class, 'index'])
            ->name('posts.index')
            ->middleware('permission:post.index');
        Route::get('posts/create', [PostController::class, 'create'])
            ->name('posts.create')
            ->middleware('permission:post.create');
        Route::post('posts', [PostController::class, 'store'])
            ->name('posts.store')
            ->middleware('permission:post.create');
        Route::get('posts/{post}/preview', [PostController::class, 'preview'])
            ->name('posts.preview')
            ->middleware('permission:post.index');
        Route::get('posts/{post}/edit', [PostController::class, 'edit'])
            ->name('posts.edit')
            ->middleware('permission:post.edit');
        Route::put('posts/{post}', [PostController::class, 'update'])
            ->name('posts.update')
            ->middleware('permission:post.edit');
        Route::delete('posts/{post}', [PostController::class, 'destroy'])
            ->name('posts.destroy')
            ->middleware('permission:post.delete');

        Route::get('categories', [CategoryController::class, 'index'])
            ->name('categories.index')
            ->middleware('permission:category.index');
        Route::get('categories/create', [CategoryController::class, 'create'])
            ->name('categories.create')
            ->middleware('permission:category.create');
        Route::post('categories', [CategoryController::class, 'store'])
            ->name('categories.store')
            ->middleware('permission:category.create');
        Route::get('categories/{category}/edit', [CategoryController::class, 'edit'])
            ->name('categories.edit')
            ->middleware('permission:category.edit');
        Route::put('categories/{category}', [CategoryController::class, 'update'])
            ->name('categories.update')
            ->middleware('permission:category.edit');
        Route::delete('categories/{category}', [CategoryController::class, 'destroy'])
            ->name('categories.destroy')
            ->middleware('permission:category.delete');

        Route::get('tags', [TagController::class, 'index'])
            ->name('tags.index')
            ->middleware('permission:tag.index');
        Route::get('tags/create', [TagController::class, 'create'])
            ->name('tags.create')
            ->middleware('permission:tag.create');
        Route::post('tags', [TagController::class, 'store'])
            ->name('tags.store')
            ->middleware('permission:tag.create');
        Route::get('tags/{tag}/edit', [TagController::class, 'edit'])
            ->name('tags.edit')
            ->middleware('permission:tag.edit');
        Route::put('tags/{tag}', [TagController::class, 'update'])
            ->name('tags.update')
            ->middleware('permission:tag.edit');
        Route::delete('tags/{tag}', [TagController::class, 'destroy'])
            ->name('tags.destroy')
            ->middleware('permission:tag.delete');
    });

// Public post detail. Two segments, so it never collides with the page
// catch-all /{slug}: slugs are unique across pages and posts (P3-04), so
// /blog/{slug} only ever looks up posts.
Route::get('/blog/{slug}', [PostPublicController::class, 'show'])
    ->name('blog.posts.show');
