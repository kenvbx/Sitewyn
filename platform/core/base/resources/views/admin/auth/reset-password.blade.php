<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reset password - {{ config('app.name', 'Sitewyn') }} Admin</title>
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
        <form class="card card-md" action="{{ route('admin.password.update') }}" method="post" autocomplete="off" novalidate>
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ old('email', $email) }}">

            <div class="card-body">
                <h2 class="card-title text-center mb-4">Reset password</h2>
                <p class="text-secondary mb-4">Enter your new password for {{ old('email', $email) }}.</p>

                @error('email')
                <div class="alert alert-danger" role="alert">{{ $message }}</div>
                @enderror

                <div class="mb-3">
                    <label class="form-label" for="password">New password</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        class="form-control @error('password') is-invalid @enderror"
                        placeholder="Your password"
                        autocomplete="new-password"
                        autofocus
                    >
                    @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password_confirmation">Confirm password</label>
                    <input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        class="form-control"
                        placeholder="Confirm password"
                        autocomplete="new-password"
                    >
                </div>
                <div class="form-footer">
                    <button type="submit" class="btn btn-primary w-100">Reset password</button>
                </div>
            </div>
        </form>
        <div class="text-center text-secondary mt-3">Remembered it? <a href="{{ route('admin.login') }}">send me back</a> to the sign in screen.</div>
    </div>
</div>
</body>
</html>
