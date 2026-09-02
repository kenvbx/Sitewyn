@extends('core/base::admin.layouts.master')

@section('title', 'Security Settings - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.system') }}">Platform Administration</a></li>
  <li class="breadcrumb-item active" aria-current="page">Security Settings</li>
@endsection

@section('content')
    <div class="row mb-5 d-block d-md-flex">
        <div class="col-12 col-md-3">
            <h2>Security Settings</h2>
            <p class="text-muted">Check and configure security settings for your website.</p>
        </div>


        <div class="col-12 col-md-9">
            <div class="card">
                <div class="card-body">
                    @if ($allConfigured)
                        <div class="alert alert-success" role="alert">
                            <div class="d-flex gap-1">
                                <div>@include('core/base::admin.partials.icon', ['name' => 'shield'])</div>
                                <div class="w-100">
                                <h4 class="alert-title mb-0">All security settings are properly configured!</h4>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-warning" role="alert">
                        <div class="d-flex gap-1">
                            <div>@include('core/base::admin.partials.icon', ['name' => 'alert-circle'])</div>
                            <div class="w-100">
                            <h4 class="alert-title mb-0">Some security settings need attention</h4>
                            </div>
                        </div>
                        </div>
                    @endif

                    <h4 class="mb-3">Current Security Settings</h4>
                <div class="table-responsive">
                    <table class="table table-vcenter">
                    <thead>
                        <tr>
                        <th class="w-1 text-center">Status</th>
                        <th>Setting</th>
                        <th class="w-1">Current Value</th>
                        <th class="w-1">Recommended</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($settings as $setting)
                        @php($matches = $setting['current'] === $setting['recommended'])
                        <tr>
                            <td class="text-center">
                            <span class="badge {{ $matches ? 'bg-success-lt text-success' : 'bg-warning-lt text-warning' }}">
                                @include('core/base::admin.partials.icon', ['name' => $matches ? 'circle-check' : 'alert-circle'])
                            </span>
                            </td>
                            <td>
                            <div class="fw-bold">{{ $setting['name'] }}</div>
                            <div class="text-muted">{{ $setting['description'] }}</div>
                            </td>
                            <td>
                            <span class="badge {{ $matches ? 'border border-success text-success' : 'border border-warning text-warning' }}">{{ $setting['current'] }}</span>
                            </td>
                            <td>
                            <span class="badge border border-primary text-primary">{{ $setting['recommended'] }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    </table>
                </div>

                <div class="card mt-4">
                    <div class="card-body">
                    <h3 class="mb-4">
                        @include('core/base::admin.partials.icon', ['name' => 'shield'])
                        Security Headers Information
                    </h3>

                    <p class="text-muted">When enabled, the following headers are added to all responses:</p>

                    <div class="table-responsive">
                        <table class="table table-vcenter mb-0">
                        <tbody>
                            @foreach ($headers as $header => $value)
                            <tr>
                                <td class="w-50">
                                <code class="border border-primary text-primary rounded px-2 py-1">{{ $header }}: {{ $value }}</code>
                                </td>
                                <td class="text-muted">
                                @switch($header)
                                    @case('X-Content-Type-Options')
                                    Prevents browsers from guessing content types
                                    @break

                                    @case('X-Frame-Options')
                                    Protects against clickjacking attacks
                                    @break

                                    @case('X-XSS-Protection')
                                    Enables browser XSS protection
                                    @break

                                    @case('Referrer-Policy')
                                    Controls how much referrer information is sent
                                    @break
                                @endswitch
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        </table>
                    </div>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </div>
@endsection
