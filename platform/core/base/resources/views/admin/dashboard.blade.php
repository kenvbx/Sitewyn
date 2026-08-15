<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Sitewyn') }} Admin</title>
    @vite(['platform/core/base/resources/css/admin.css', 'platform/core/base/resources/js/admin.js'])
</head>
<body>
<div class="page">
    <header class="navbar navbar-expand-md d-print-none">
        <div class="container-xl">
            <h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
                {{ config('app.name', 'Sitewyn') }}
            </h1>
            <div class="navbar-nav flex-row order-md-last ms-auto">
                <form method="post" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary">Logout</button>
                </form>
            </div>
        </div>
    </header>

    <div class="page-wrapper">
        <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="row g-2 align-items-center">
                    <div class="col">
                        <div class="page-pretitle">Admin</div>
                        <h2 class="page-title">Dashboard</h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                <div class="card">
                    <div class="card-body">
                        <div class="text-secondary">Signed in as</div>
                        <div class="fw-bold">{{ auth('admin')->user()?->email }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
