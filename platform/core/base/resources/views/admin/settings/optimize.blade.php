@extends('core/base::admin.layouts.master')

@section('title', 'Optimize - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.settings.edit') }}">Settings</a></li>
  <li class="breadcrumb-item active" aria-current="page">Optimize</li>
@endsection

@section('content')
  @php($pageSpeedEnabled = old('optimize_page_speed_enabled', $settings['optimize_page_speed_enabled']))

  @if (session('status'))
    <div class="alert alert-success" role="alert">{{ session('status') }}</div>
  @endif

  <form id="optimize-settings-form" method="POST" action="{{ route('admin.settings.optimize.update', [], false) }}" class="needs-validation" data-admin-validate novalidate>
    @csrf
    @method('PUT')
    <input type="hidden" name="site_name" value="{{ $baseSettings['site_name'] }}">
    <input type="hidden" name="site_logo" value="{{ $baseSettings['site_logo'] }}">
    <input type="hidden" name="robots_txt" value="{{ $baseSettings['robots_txt'] }}">
    <input type="hidden" name="active_theme" value="{{ $baseSettings['active_theme'] }}">

    <div class="row mb-5 d-block d-md-flex">
      <div class="col-12 col-md-3">
        <h2>Optimize</h2>
        <p class="text-muted">Minify HTML output, inline CSS, remove comments...</p>
      </div>

      <div class="col-12 col-md-9">
        <div class="card">
          <div class="card-body">
            <div class="mb-4">
              <label class="form-check">
                <input type="hidden" name="optimize_page_speed_enabled" value="0">
                <input type="checkbox" name="optimize_page_speed_enabled" value="1" class="form-check-input" @checked($pageSpeedEnabled) data-optimize-page-speed-toggle>
                <span class="form-check-label">Enable optimize page speed?</span>
              </label>
            </div>

            <div class="border rounded p-4 {{ ! $pageSpeedEnabled ? 'd-none' : '' }}" data-optimize-page-speed-panel>
              @foreach ($filters as $filter)
                <div class="{{ ! $loop->last ? 'mb-4' : 'mb-0' }}">
                  <label class="form-check">
                    <input type="hidden" name="{{ $filter['key'] }}" value="0">
                    <input type="checkbox" name="{{ $filter['key'] }}" value="1" class="form-check-input" @checked(old($filter['key'], $settings[$filter['key']]))>
                    <span class="form-check-label">{{ $filter['label'] }}</span>
                  </label>
                  <div class="form-hint ms-4">{{ $filter['description'] }}</div>
                </div>
              @endforeach
            </div>
          </div>
        </div>
      </div>
    </div>
  </form>

  <div class="row mb-5 d-block d-md-flex">
    <div class="col-12 col-md-3"></div>
    <div class="col-12 col-md-9">
      <button type="submit" class="btn btn-primary btn-lg" form="optimize-settings-form">
        @include('core/base::admin.partials.icon', ['name' => 'save'])
        <span class="ms-2">Save settings</span>
      </button>
    </div>
  </div>
@endsection

@push('scripts')
  <script>
    ;(function () {
      var toggle = document.querySelector('[data-optimize-page-speed-toggle]')
      var panel = document.querySelector('[data-optimize-page-speed-panel]')

      function syncPanel() {
        if (toggle && panel) {
          panel.classList.toggle('d-none', !toggle.checked)
        }
      }

      toggle && toggle.addEventListener('change', syncPanel)
      syncPanel()
    })()
  </script>
@endpush
