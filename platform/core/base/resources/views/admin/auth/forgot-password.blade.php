<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Forgot password - {{ config('app.name', 'Sitewyn') }} Admin</title>
    <meta name="msapplication-TileColor" content="#066fd1">
    <meta name="theme-color" content="#066fd1">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="HandheldFriendly" content="True">
    <meta name="MobileOptimized" content="320">
    @vite(['platform/core/base/resources/css/admin.css', 'platform/core/base/resources/js/admin.js'])
</head>
<body>
<div class="page page-center">
    <div class="container container-tight py-4">
        <div class="text-center mb-4">
            <a href="{{ route('admin.login') }}" aria-label="{{ config('app.name', 'Sitewyn') }}" class="navbar-brand navbar-brand-autodark">
                <span class="navbar-brand-image fw-bold fs-2 text-primary">{{ config('app.name', 'Sitewyn') }}</span>
            </a>
        </div>
        <form class="card card-md" action="{{ route('admin.password.email') }}" method="post" autocomplete="off" novalidate>
            @csrf
            <div class="card-body">
                <h2 class="card-title text-center mb-4">Forgot password</h2>
                <p class="text-secondary mb-4">Enter your email address and your password will be reset and emailed to you.</p>

                @if (session('status'))
                    <div class="alert alert-success" role="alert">{{ session('status') }}</div>
                @endif

                <div class="mb-3">
                    <label class="form-label" for="email">Email address</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        class="form-control @error('email') is-invalid @enderror"
                        placeholder="Enter email"
                        autocomplete="off"
                        autofocus
                    >
                    @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-footer">
                    <button type="submit" class="btn btn-primary btn-4 w-100">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             class="icon icon-2">
                            <path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10z"></path>
                            <path d="M3 7l9 6l9 -6"></path>
                        </svg>
                        Send me new password
                    </button>
                </div>
            </div>
        </form>
        <div class="text-center text-secondary mt-3">Forget it, <a href="{{ route('admin.login') }}">send me back</a> to the sign in screen.</div>
    </div>
</div>
</body>
</html>
