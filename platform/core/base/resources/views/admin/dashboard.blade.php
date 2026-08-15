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
@php
    $adminBrand = 'Sitewyn';
    $adminUser = auth('admin')->user();
    $adminName = $adminUser?->name ?: 'Administrator';
    $adminInitials = collect(explode(' ', $adminName))->filter()->map(fn (string $part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))->take(2)->implode('') ?: 'AD';
@endphp
<div class="page">
    <aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu" aria-controls="sidebar-menu" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="navbar-brand navbar-brand-autodark">
                <a href="{{ route('admin.dashboard') }}" aria-label="{{ $adminBrand }}">
                    <span class="navbar-brand-image d-inline-flex align-items-center justify-content-center rounded bg-primary text-white fw-bold" style="width: 32px; height: 32px;">S</span>
                    <span class="ms-2">{{ $adminBrand }}</span>
                </a>
            </div>
            <div class="navbar-nav flex-row d-lg-none">
                <div class="nav-item dropdown">
                    <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Open user menu">
                        <span class="avatar avatar-sm">{{ $adminInitials }}</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                        <div class="dropdown-header">{{ $adminUser?->email }}</div>
                        <form method="post" action="{{ route('admin.logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item">Logout</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="collapse navbar-collapse" id="sidebar-menu">
                <ul class="navbar-nav pt-lg-3">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admin.dashboard') }}">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M5 12l-2 0l9 -9l9 9l-2 0" />
                                    <path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7" />
                                    <path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6" />
                                </svg>
                            </span>
                            <span class="nav-link-title">Home</span>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#navbar-content" data-bs-toggle="dropdown" data-bs-auto-close="false" role="button" aria-expanded="true">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 19.5a2.5 2.5 0 0 1 2.5 -2.5h13.5" />
                                    <path d="M4 4.5a2.5 2.5 0 0 1 2.5 -2.5h13.5v20h-13.5a2.5 2.5 0 0 1 0 -5" />
                                </svg>
                            </span>
                            <span class="nav-link-title">Content</span>
                        </a>
                        <div class="dropdown-menu show">
                            <a class="dropdown-item" href="#">Posts</a>
                            <a class="dropdown-item" href="#">Pages</a>
                            <a class="dropdown-item" href="#">Media</a>
                            <a class="dropdown-item" href="#">Comments</a>
                        </div>
                    </li>
                    <li class="nav-item dropdown active">
                        <a class="nav-link dropdown-toggle" href="#navbar-layout" data-bs-toggle="dropdown" data-bs-auto-close="false" role="button" aria-expanded="true">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 4m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v2h-16z" />
                                    <path d="M4 10h16" />
                                    <path d="M9 10v10" />
                                    <path d="M6 20h12a2 2 0 0 0 2 -2v-8h-16v8a2 2 0 0 0 2 2z" />
                                </svg>
                            </span>
                            <span class="nav-link-title">Layout</span>
                        </a>
                        <div class="dropdown-menu show">
                            <a class="dropdown-item" href="#">Horizontal</a>
                            <a class="dropdown-item active" href="{{ route('admin.dashboard') }}">Combined</a>
                            <a class="dropdown-item" href="#">Navbar dark</a>
                            <a class="dropdown-item" href="#">Navbar sticky</a>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 3l8 4.5l0 9l-8 4.5l-8 -4.5l0 -9l8 -4.5" />
                                    <path d="M12 12l8 -4.5" />
                                    <path d="M12 12l0 9" />
                                    <path d="M12 12l-8 -4.5" />
                                </svg>
                            </span>
                            <span class="nav-link-title">Plugins</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </aside>

    <header class="navbar navbar-expand-md d-none d-lg-flex d-print-none">
        <div class="container-xl">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu" aria-controls="navbar-menu" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="navbar-nav flex-row order-md-last">
                <div class="d-none d-md-flex">
                    <a href="#" class="nav-link px-0" title="Enable dark mode" data-bs-toggle="tooltip" data-bs-placement="bottom">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 3c.132 0 .263 0 .393 0a7.5 7.5 0 0 0 7.92 12.446a9 9 0 1 1 -8.313 -12.454z" />
                        </svg>
                    </a>
                    <div class="nav-item dropdown d-none d-md-flex">
                        <a href="#" class="nav-link px-0" data-bs-toggle="dropdown" tabindex="-1" aria-label="Show notifications">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M10 5a2 2 0 1 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6" />
                                <path d="M9 17v1a3 3 0 0 0 6 0v-1" />
                            </svg>
                            <span class="badge bg-red"></span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-arrow dropdown-menu-end dropdown-menu-card">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Notifications</h3>
                                </div>
                                <div class="list-group list-group-flush list-group-hoverable">
                                    <div class="list-group-item">
                                        <div class="row align-items-center">
                                            <div class="col-auto"><span class="status-dot status-dot-animated bg-green d-block"></span></div>
                                            <div class="col text-truncate">
                                                <span class="text-body d-block">System ready</span>
                                                <div class="d-block text-secondary text-truncate mt-n1">Admin workspace is available.</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="nav-item dropdown">
                    <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Open user menu">
                        <span class="avatar avatar-sm">{{ $adminInitials }}</span>
                        <div class="d-none d-xl-block ps-2">
                            <div>{{ $adminName }}</div>
                            <div class="mt-1 small text-secondary">{{ $adminUser?->email }}</div>
                        </div>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                        <a href="#" class="dropdown-item">Profile</a>
                        <a href="#" class="dropdown-item">Settings</a>
                        <div class="dropdown-divider"></div>
                        <form method="post" action="{{ route('admin.logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item">Logout</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <header class="navbar-expand-md">
        <div class="collapse navbar-collapse" id="navbar-menu">
            <div class="navbar">
                <div class="container-xl">
                    <ul class="navbar-nav">
                        <li class="nav-item active">
                            <a class="nav-link" href="{{ route('admin.dashboard') }}">
                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M5 12l-2 0l9 -9l9 9l-2 0" />
                                        <path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7" />
                                        <path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6" />
                                    </svg>
                                </span>
                                <span class="nav-link-title">Home</span>
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#navbar-interface" data-bs-toggle="dropdown" data-bs-auto-close="outside" role="button" aria-expanded="false">
                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 3l8 4.5l0 9l-8 4.5l-8 -4.5l0 -9l8 -4.5" />
                                        <path d="M12 12l8 -4.5" />
                                        <path d="M12 12l0 9" />
                                        <path d="M12 12l-8 -4.5" />
                                    </svg>
                                </span>
                                <span class="nav-link-title">Interface</span>
                            </a>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="#">Buttons</a>
                                <a class="dropdown-item" href="#">Cards</a>
                                <a class="dropdown-item" href="#">Data grid</a>
                            </div>
                        </li>
                        <li class="nav-item"><a class="nav-link" href="#"><span class="nav-link-title">Forms</span></a></li>
                        <li class="nav-item"><a class="nav-link" href="#"><span class="nav-link-title">Extra</span></a></li>
                        <li class="nav-item dropdown active">
                            <a class="nav-link dropdown-toggle" href="#navbar-layout-menu" data-bs-toggle="dropdown" data-bs-auto-close="outside" role="button" aria-expanded="false">
                                <span class="nav-link-title">Layout</span>
                            </a>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="#">Horizontal</a>
                                <a class="dropdown-item active" href="{{ route('admin.dashboard') }}">Combined</a>
                                <a class="dropdown-item" href="#">Vertical</a>
                            </div>
                        </li>
                        <li class="nav-item"><a class="nav-link" href="#"><span class="nav-link-title">Plugins</span></a></li>
                        <li class="nav-item"><a class="nav-link" href="#"><span class="nav-link-title">Addons</span></a></li>
                        <li class="nav-item"><a class="nav-link" href="#"><span class="nav-link-title">Help</span></a></li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <div class="page-wrapper">
        <div class="page-header d-print-none" aria-label="Page header">
            <div class="container-xl">
                <div class="row g-2 align-items-center">
                    <div class="col">
                        <div class="page-pretitle">Overview</div>
                        <h2 class="page-title">Combo layout</h2>
                    </div>
                    <div class="col-auto ms-auto d-print-none">
                        <div class="btn-list">
                            <span class="d-none d-sm-inline">
                                <a href="#" class="btn btn-1">New view</a>
                            </span>
                            <a href="#" class="btn btn-primary btn-5 d-none d-sm-inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 5l0 14" />
                                    <path d="M5 12l14 0" />
                                </svg>
                                Create new report
                            </a>
                            <a href="#" class="btn btn-primary btn-6 d-sm-none btn-icon" aria-label="Create new report">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 5l0 14" />
                                    <path d="M5 12l14 0" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                <div class="row row-deck row-cards">
                    <div class="col-sm-12 col-lg-6">
                        <div class="card">
                            <div class="card-body">
                                <div class="row gy-3">
                                    <div class="col-12 col-sm d-flex flex-column">
                                        <h3 class="h2">Welcome back, {{ $adminName }}</h3>
                                        <p class="text-muted">You have 5 new messages and 2 new notifications.</p>
                                        <div class="row g-5 mt-auto">
                                            <div class="col-auto">
                                                <div class="subheader">Today's Views</div>
                                                <div class="d-flex align-items-baseline">
                                                    <div class="h3 me-2">6,782</div>
                                                    <div class="me-auto">
                                                        <span class="text-green d-inline-flex align-items-center lh-1">
                                                            7%
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon ms-1 icon-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                                <path d="M3 17l6 -6l4 4l8 -8" />
                                                                <path d="M14 7l7 0l0 7" />
                                                            </svg>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <div class="subheader">New Comments</div>
                                                <div class="d-flex align-items-baseline">
                                                    <div class="h3 me-2">128</div>
                                                    <div class="me-auto">
                                                        <span class="text-red d-inline-flex align-items-center lh-1">
                                                            3%
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon ms-1 icon-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                                <path d="M3 7l6 6l4 -4l8 8" />
                                                                <path d="M21 10l0 7l-7 0" />
                                                            </svg>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-auto">
                                        <span class="avatar avatar-xl avatar-rounded">{{ $adminInitials }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="subheader">Total Users</div>
                                    <div class="ms-auto lh-1">
                                        <span class="text-secondary">Last 7 days</span>
                                    </div>
                                </div>
                                <div class="h1 mb-3">1,284</div>
                                <div class="d-flex mb-2">
                                    <div>Conversion rate</div>
                                    <div class="ms-auto">
                                        <span class="text-green d-inline-flex align-items-center lh-1">12%</span>
                                    </div>
                                </div>
                                <div class="progress progress-sm">
                                    <div class="progress-bar bg-primary" style="width: 75%" role="progressbar" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100" aria-label="75% Complete"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="subheader">Active Sessions</div>
                                    <div class="ms-auto lh-1">
                                        <span class="text-secondary">Today</span>
                                    </div>
                                </div>
                                <div class="h1 mb-3">342</div>
                                <div class="d-flex mb-2">
                                    <div>Server load</div>
                                    <div class="ms-auto">
                                        <span class="text-green d-inline-flex align-items-center lh-1">48%</span>
                                    </div>
                                </div>
                                <div class="progress progress-sm">
                                    <div class="progress-bar bg-green" style="width: 48%" role="progressbar" aria-valuenow="48" aria-valuemin="0" aria-valuemax="100" aria-label="48% Complete"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Traffic summary</h3>
                            </div>
                            <div class="card-body">
                                <div class="row g-2 align-items-center">
                                    <div class="col-auto">
                                        <span class="status-dot status-dot-animated bg-green d-block"></span>
                                    </div>
                                    <div class="col">
                                        <div class="text-secondary">Signed in as</div>
                                        <div class="fw-bold">{{ $adminUser?->email }}</div>
                                    </div>
                                    <div class="col-auto">
                                        <span class="badge bg-green-lt">Online</span>
                                    </div>
                                </div>
                                <div class="row g-3 mt-3">
                                    <div class="col-sm-4">
                                        <div class="subheader">Posts</div>
                                        <div class="h2">124</div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="subheader">Pages</div>
                                        <div class="h2">32</div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="subheader">Media</div>
                                        <div class="h2">786</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Locations</h3>
                            </div>
                            <div class="list-group list-group-flush">
                                <div class="list-group-item">
                                    <div class="row align-items-center">
                                        <div class="col">Vietnam</div>
                                        <div class="col-auto text-secondary">64%</div>
                                    </div>
                                </div>
                                <div class="list-group-item">
                                    <div class="row align-items-center">
                                        <div class="col">United States</div>
                                        <div class="col-auto text-secondary">18%</div>
                                    </div>
                                </div>
                                <div class="list-group-item">
                                    <div class="row align-items-center">
                                        <div class="col">Singapore</div>
                                        <div class="col-auto text-secondary">12%</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
