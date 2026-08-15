<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Sitewyn') }} Admin</title>
    <meta name="msapplication-TileColor" content="#066fd1">
    <meta name="theme-color" content="#066fd1">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="HandheldFriendly" content="True">
    <meta name="MobileOptimized" content="320">
    @vite(['platform/core/base/resources/css/admin.css', 'platform/core/base/resources/js/admin.js'])
</head>
<body class="d-flex flex-column bg-white">
<div class="row g-0 flex-fill">
    <div class="col-12 col-lg-6 col-xl-4 border-top-wide border-primary d-flex flex-column justify-content-center">
        <div class="container container-tight my-5 px-lg-5">
            <div class="text-center mb-4">
                <a href="{{ route('admin.login') }}" aria-label="{{ config('app.name', 'Sitewyn') }}" class="navbar-brand navbar-brand-autodark">
                    <span class="navbar-brand-image fw-bold fs-2 text-primary">{{ config('app.name', 'Sitewyn') }}</span>
                </a>
            </div>
            <h2 class="h3 text-center mb-3">Login to your account</h2>
            <form action="{{ route('admin.login.store') }}" method="post" autocomplete="off" novalidate>
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="email">Email address</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        class="form-control @error('email') is-invalid @enderror"
                        placeholder="your@email.com"
                        autocomplete="off"
                        autofocus
                    >
                    @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-2">
                    <label class="form-label" for="password">
                        Password
                        <span class="form-label-description">
                            <a href="#">I forgot password</a>
                        </span>
                    </label>
                    <div class="input-group input-group-flat">
                        <input
                            id="password"
                            type="password"
                            name="password"
                            class="form-control @error('password') is-invalid @enderror"
                            placeholder="Your password"
                            autocomplete="off"
                        >
                        <span class="input-group-text">
                            <a href="#" class="link-secondary" title="Show password" data-bs-toggle="tooltip">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                     class="icon icon-1">
                                    <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"></path>
                                    <path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"></path>
                                </svg>
                            </a>
                        </span>
                    </div>
                    @error('password')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-2">
                    <label class="form-check">
                        <input type="checkbox" name="remember" value="1" class="form-check-input" @checked(old('remember'))>
                        <span class="form-check-label">Remember me on this device</span>
                    </label>
                </div>
                <div class="form-footer">
                    <button type="submit" class="btn btn-primary w-100">Sign in</button>
                </div>
            </form>
            <div class="text-center text-secondary mt-3">Don't have account yet? <a href="#" tabindex="-1">Sign up</a></div>
        </div>
    </div>
    <div class="col-12 col-lg-6 col-xl-8 d-none d-lg-block">
        <div
            class="bg-cover h-100 min-vh-100"
            style="background-image: url(https://preview.tabler.io/static/photos/finances-us-dollars-and-bitcoins-currency-money-2.jpg)"
        ></div>
    </div>
</div>
</body>
</html>
