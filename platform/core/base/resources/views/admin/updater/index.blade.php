@extends('core/base::admin.layouts.master')

@section('title', 'System Updater - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('pretitle', 'System')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.system') }}">Platform Administration</a></li>
  <li class="breadcrumb-item active" aria-current="page">System Updater</li>
@endsection

@section('content')
  <div class="row justify-content-center">
    <div class="col-xl-8">
      @if (session('status'))
        <div class="alert alert-success" role="alert">
          <div class="d-flex gap-2">
            <div>@include('core/base::admin.partials.icon', ['name' => 'circle-check'])</div>
            <div>{{ session('status') }}</div>
          </div>
        </div>
      @endif

      @if (session('error'))
        <div class="alert alert-danger" role="alert">
          <div class="d-flex gap-2">
            <div>@include('core/base::admin.partials.icon', ['name' => 'alert-circle'])</div>
            <div>{{ session('error') }}</div>
          </div>
        </div>
      @endif

      <div class="alert alert-warning mb-4" role="alert">
        <div class="d-flex gap-2">
          <div>@include('core/base::admin.partials.icon', ['name' => 'alert-circle'])</div>
          <div>
            <h4 class="alert-title">Important notes:</h4>
            <ul class="mb-0">
              <li>Please back up your database and script files before upgrading.</li>
              <li>If you don't need this 1-click update, you can disable it in <strong>.env</strong> by adding <strong>CMS_ENABLE_SYSTEM_UPDATER=false</strong></li>
              <li>It will override all files in <strong>platform/core</strong>, <strong>platform/packages</strong>, all plugins developed by us in <strong>platform/plugins</strong> and theme developed by us in <strong>platform/themes</strong>.</li>
            </ul>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header">
          <h2 class="card-title">OneClick System Updater</h2>
        </div>
        <div class="card-body">
          @if (! $status['enabled'])
            <div class="h3 text-warning">System updater is disabled.</div>
            <p class="text-secondary mb-0">Set <code>CMS_ENABLE_SYSTEM_UPDATER=true</code> to enable one-click and manual updater actions.</p>
          @elseif ($status['upToDate'])
            <h3 class="text-success">The system is up-to-date. There are no new versions to update!</h3>
            <form method="POST" action="{{ route('admin.system.updater.reinstall', [], false) }}" class="mt-3">
              @csrf
              <button type="submit" class="btn btn-warning btn-lg text-white">
                @include('core/base::admin.partials.icon', ['name' => 'reload'])
                <span class="ms-2">Re-install The Latest Version</span>
              </button>
            </form>
            <p class="mt-4 mb-0">This won't touch or reset your data - it just reinstall the latest version of the system.</p>
          @else
            <div class="h3 text-primary">A new version {{ $status['latestVersion'] }} is available.</div>
            <form method="POST" action="{{ route('admin.system.updater.reinstall', [], false) }}" class="mt-3">
              @csrf
              <button type="submit" class="btn btn-primary btn-lg">
                @include('core/base::admin.partials.icon', ['name' => 'download'])
                <span class="ms-2">Update Now</span>
              </button>
            </form>
          @endif
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header">
          <h2 class="card-title">Manual System Updater</h2>
        </div>
        <div class="card-body">
          <div class="alert alert-info" role="alert">
            <div class="d-flex gap-2">
              <div>@include('core/base::admin.partials.icon', ['name' => 'info-circle'])</div>
              <div>Having trouble with the One-Click System Updater? No worries! Check out the manual updater below - it's easy, just follow the steps!</div>
            </div>
          </div>

          <ol class="steps steps-vertical steps-counter steps-blue mt-4">
            @foreach ($steps as $step)
              @php($done = isset($completedSteps[$step['number']]))
              <li class="step-item {{ $done ? '' : 'active' }}">
                <div class="d-flex flex-column flex-sm-row align-items-start gap-2">
                  <div class="flex-fill">
                    <div class="h3 mb-2">{{ $step['title'] }}</div>
                    @if ($done)
                      <div class="text-success mb-2">
                        @include('core/base::admin.partials.icon', ['name' => 'circle-check'])
                        <span class="ms-1">Completed at {{ $completedSteps[$step['number']]['finished_at'] }}</span>
                      </div>
                    @else
                      <div class="text-secondary mb-2">{{ $step['description'] }}</div>
                    @endif
                    <form method="POST" action="{{ route('admin.system.updater.steps.run', ['step' => $step['number']], false) }}">
                      @csrf
                      <button type="submit" class="btn btn-primary" @disabled(! $status['enabled'])>Run</button>
                    </form>
                  </div>
                </div>
              </li>
            @endforeach
          </ol>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h2 class="card-title">Latest changelog ({{ $status['latestDate'] }})</h2>
        </div>
        <div class="card-body">
          <pre class="bg-dark text-white p-4 rounded mb-0"><code>{{ $status['changelog'] }}</code></pre>
        </div>
      </div>
    </div>
  </div>
@endsection
