@extends('core/base::admin.layouts.master')

@section('title', 'Cache Management - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('pretitle', 'System')

@section('page-title', 'Cache Management')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.system') }}">Platform Administration</a></li>
  <li class="breadcrumb-item active" aria-current="page">Cache Management</li>
@endsection

@section('content')
  <div class="card mb-4">
    <div class="card-header">
      <h2 class="card-title">
        @include('core/base::admin.partials.icon', ['name' => 'reload'])
        Cache Management
      </h2>
    </div>
    <div class="card-body">
      <p class="text-muted fs-3">Clear cache to make your site up to date.</p>

      @include('core/base::admin.cache.partials.operations-table', ['rows' => $cacheRows])
    </div>
    <div class="card-footer text-muted">
      @include('core/base::admin.partials.icon', ['name' => 'info-circle'])
      <span class="ms-2">Clear cache after making changes to your site to ensure they appear correctly.</span>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h2 class="card-title">
        @include('core/base::admin.partials.icon', ['name' => 'rocket'])
        Performance Optimization
      </h2>
    </div>
    <div class="card-body">
      @include('core/base::admin.cache.partials.operations-table', ['rows' => $optimizationRows])
    </div>
  </div>
@endsection
