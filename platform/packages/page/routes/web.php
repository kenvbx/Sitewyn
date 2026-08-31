<?php

use Illuminate\Support\Facades\Route;
use Sitewyn\Packages\Page\Http\Controllers\Admin\PageController;
use Sitewyn\Packages\Page\Http\Controllers\Public\PagePublicController;

Route::get('/_platform/package/page', static fn () => response()->json([
    'module' => 'package/page',
    'status' => 'ok',
]))->name('platform.package.page.health');

Route::prefix('admin')
    ->middleware(['web', 'auth:admin'])
    ->name('admin.')
    ->group(function (): void {
        Route::get('pages', [PageController::class, 'index'])
            ->name('pages.index')
            ->middleware('permission:page.index');
        Route::get('pages/create', [PageController::class, 'create'])
            ->name('pages.create')
            ->middleware('permission:page.create');
        Route::post('pages', [PageController::class, 'store'])
            ->name('pages.store')
            ->middleware('permission:page.create');
        Route::get('pages/{page}/preview', [PageController::class, 'preview'])
            ->name('pages.preview')
            ->middleware('permission:page.index');
        Route::get('pages/{page}/edit', [PageController::class, 'edit'])
            ->name('pages.edit')
            ->middleware('permission:page.edit');
        Route::put('pages/{page}', [PageController::class, 'update'])
            ->name('pages.update')
            ->middleware('permission:page.edit');
        Route::delete('pages/{page}', [PageController::class, 'destroy'])
            ->name('pages.destroy')
            ->middleware('permission:page.delete');
    });

// Localized page detail (P5-01): /{locale}/{slug} reuses the default
// language's slug — translations never own slugs. It must stay BEFORE the
// /{slug} catch-all below, and the two-segment shape never collides with the
// routes it could otherwise swallow: the locale regex is exactly two
// lowercase letters, so reserved first segments (admin, blog, api, storage,
// _platform, ...) can never match, and the extra segment keeps 1-segment
// URLs (/up, /login) and the blog package's /blog/{slug} out of this route.
Route::get('/{locale}/{slug}', [PagePublicController::class, 'showLocalized'])
    ->name('pages.localized')
    ->where('locale', '[a-z]{2}')
    ->where('slug', '[^/]+');

// Public page catch-all. It must stay the LAST route in this file so every
// route above (and in every module loaded before it) is matched first, and the
// where() regex keeps reserved first segments out of page lookups — without it,
// single-segment URLs like /blog or /up would be swallowed by this route
// instead of falling through to the framework's 404. The parameter regex must
// be [^/]+ (not .+): a custom requirement replaces Symfony's default [^/]+, and
// .+ would span slashes and swallow multi-segment paths like /_platform/....
// sitemap.xml / robots.txt are core-owned public SEO files (P5-08); core routes
// are registered before package routes and win anyway, but a page row named
// "sitemap.xml" must never serve as a page even if route order ever changes.
Route::get('/{slug}', [PagePublicController::class, 'show'])
    ->name('pages.show')
    ->where('slug', '^(?!admin$|blog$|api$|_platform$|storage$|build$|vendor$|up$|login$|logout$|register$|password$|reset$|sitemap\.xml$|robots\.txt$)[^/]+$');
