@extends('core/base::admin.layouts.master')

@section('title', 'Cleanup System - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('pretitle', 'System')

@section('page-title', 'Cleanup System')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.system') }}">Platform Administration</a></li>
  <li class="breadcrumb-item active" aria-current="page">Cleanup System</li>
@endsection

@section('content')
  <div class="row justify-content-center">
    <div class="col-xl-8">
      <div class="alert alert-warning mb-4" role="alert">
        <div class="d-flex gap-2">
          <div>@include('core/base::admin.partials.icon', ['name' => 'alert-circle'])</div>
          <div>
            <p class="mb-3">Please backup your database and script files before cleanup, it will clear your data in database.</p>

            @unless ($enabled)
              <p class="mb-0">
                This featured is not enabled yet.<br>
                Please add into .env: <code>CMS_ENABLED_CLEANUP_DATABASE=true</code> to enable this feature!
              </p>
            @else
              <p class="mb-0">Cleanup is enabled. Tables checked below will be ignored and kept safe.</p>
            @endunless
          </div>
        </div>
      </div>

      <form method="POST" action="{{ route('admin.system.cleanup.run', [], false) }}">
        @csrf

        <div class="card">
          <div class="card-header">
            <h2 class="card-title">Please choose to ignore tables that do not want to be cleaned:</h2>
          </div>

          <div class="table-responsive">
            <table class="table table-vcenter card-table">
              <thead>
                <tr>
                  <th class="w-1">#</th>
                  <th>Table Name</th>
                  <th class="w-1 text-end">Records</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($tables as $table)
                  <tr>
                    <td>
                      <input class="form-check-input m-0"
                             type="checkbox"
                             name="ignored_tables[]"
                             value="{{ $table['name'] }}"
                             @checked($table['ignored'])
                             aria-label="Ignore {{ $table['name'] }}">
                    </td>
                    <td class="fs-3">{{ $table['name'] }}</td>
                    <td class="fs-3 text-end">{{ number_format($table['records']) }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="3" class="text-center text-muted py-5">No database tables found.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <div class="card-footer text-end">
            <button type="submit" class="btn btn-danger btn-lg" @disabled(! $enabled || $tables === [])>
              @include('core/base::admin.partials.icon', ['name' => 'alert-circle'])
              <span class="ms-2">Cleanup</span>
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
@endsection
