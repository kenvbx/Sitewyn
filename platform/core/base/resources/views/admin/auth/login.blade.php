<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Sitewyn') }} Admin</title>
    @vite(['platform/core/base/resources/css/admin.css', 'platform/core/base/resources/js/admin.js'])
</head>
<body class="d-flex flex-column bg-body-tertiary">
<main class="page page-center">
    <div class="container container-tight py-4">
        <div class="text-center mb-4">
            <h1 class="h2 mb-1">{{ config('app.name', 'Sitewyn') }}</h1>
            <div class="text-secondary">Admin</div>
        </div>

        <form class="card card-md" action="{{ route('admin.login.store') }}" method="post" autocomplete="off" novalidate>
            @csrf
            <div class="card-body">
                <h2 class="h2 text-center mb-4">Sign in</h2>

                <div class="mb-3">
                    <label class="form-label" for="email">Email address</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        class="form-control @error('email') is-invalid @enderror"
                        autocomplete="email"
                        autofocus
                        required
                    >
                    @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-2">
                    <label class="form-label" for="password">Password</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        class="form-control @error('password') is-invalid @enderror"
                        autocomplete="current-password"
                        required
                    >
                    @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <label class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="remember" value="1" @checked(old('remember'))>
                    <span class="form-check-label">Remember me</span>
                </label>

                <div class="form-footer">
                    <button type="submit" class="btn btn-primary w-100">Sign in</button>
                </div>
            </div>
        </form>
    </div>
</main>
</body>
</html>
