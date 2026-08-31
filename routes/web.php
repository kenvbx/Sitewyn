<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

// CMS front page (P5-02): the WordPress-style home lists the latest
// published posts (plus a compact page index) through the active theme,
// replacing the Laravel welcome boilerplate. It is the only route here and
// an exact match on `/`, so it cannot shadow the page package's /{slug}
// catch-all or the blog package's /blog/{slug} registered in their modules.
Route::get('/', [HomeController::class, 'index'])->name('home');
