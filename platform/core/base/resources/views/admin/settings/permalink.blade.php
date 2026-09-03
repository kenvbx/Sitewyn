@extends('core/base::admin.layouts.master')

@section('title', 'Permalink settings - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.settings.edit') }}">Settings</a></li>
  <li class="breadcrumb-item active" aria-current="page">Permalink</li>
@endsection

@section('content')
  @if (session('status'))
    <div class="alert alert-success" role="alert">{{ session('status') }}</div>
  @endif

  <form id="permalink-settings-form" method="POST" action="{{ route('admin.settings.permalink.update', [], false) }}" class="needs-validation" data-admin-validate novalidate>
    @csrf
    @method('PUT')
    <input type="hidden" name="site_name" value="{{ $baseSettings['site_name'] }}">
    <input type="hidden" name="site_logo" value="{{ $baseSettings['site_logo'] }}">
    <input type="hidden" name="robots_txt" value="{{ $baseSettings['robots_txt'] }}">
    <input type="hidden" name="active_theme" value="{{ $baseSettings['active_theme'] }}">

    <div class="row mb-5 d-block d-md-flex">
      <div class="col-12 col-md-3">
        <h2>Permalink settings</h2>
        <p class="text-muted">Manage permalink for all modules.</p>
      </div>

      <div class="col-12 col-md-9">
        <div class="card">
          <div class="card-body">
            @foreach ($fields as $field)
              @php
                $previewValue = old($field['key'], $settings[$field['key']]);
                $previewUrl = $baseUrl . '/your-url-here';

                if ($field['type'] === 'postfix') {
                    $previewUrl = $baseUrl . '/your-url-here' . trim($previewValue);
                } elseif (trim($previewValue, " \t\n\r\0\x0B/") !== '') {
                    $previewUrl = $baseUrl . '/' . trim($previewValue, " \t\n\r\0\x0B/") . '/your-url-here';
                }
              @endphp

              <div class="mb-3">
                <label class="form-label" for="{{ str_replace('_', '-', $field['key']) }}">{{ $field['label'] }}</label>
                <input
                  type="text"
                  name="{{ $field['key'] }}"
                  id="{{ str_replace('_', '-', $field['key']) }}"
                  value="{{ old($field['key'], $settings[$field['key']]) }}"
                  class="form-control"
                  placeholder="{{ $field['placeholder'] }}"
                  data-permalink-input
                  data-permalink-type="{{ $field['type'] }}"
                  data-permalink-preview="{{ str_replace('_', '-', $field['key']) }}-preview"
                >
                <div class="form-hint">
                  Preview:
                  <a href="{{ $previewUrl }}" class="text-decoration-none" id="{{ str_replace('_', '-', $field['key']) }}-preview" data-permalink-preview-link data-base-url="{{ $baseUrl }}">{{ $previewUrl }}</a>
                </div>
              </div>
            @endforeach

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-lg-between gap-3">
              <label class="form-check form-switch mb-0">
                <input type="hidden" name="permalink_turn_off_automatic_url_translation_into_latin" value="0">
                <input type="checkbox" name="permalink_turn_off_automatic_url_translation_into_latin" value="1" class="form-check-input" @checked(old('permalink_turn_off_automatic_url_translation_into_latin', $settings['permalink_turn_off_automatic_url_translation_into_latin']))>
                <span class="form-check-label">Turn off automatic URL translation into Latin?</span>
              </label>

              <div class="d-flex align-items-center justify-content-end gap-2 text-secondary">
                <span class="fw-bold text-body">Translations:</span>
                <span class="badge bg-blue-lt">EN</span>
                <select class="form-select form-select-sm w-auto" aria-label="Permalink translation language">
                  <option selected>English</option>
                </select>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </form>

  <div class="row mb-5 d-block d-md-flex">
    <div class="col-12 col-md-3"></div>
    <div class="col-12 col-md-9">
      <button type="submit" class="btn btn-primary btn-lg" form="permalink-settings-form">
        @include('core/base::admin.partials.icon', ['name' => 'save'])
        <span class="ms-2">Save settings</span>
      </button>
    </div>
  </div>
@endsection

@push('scripts')
  <script>
    ;(function () {
      var inputs = document.querySelectorAll('[data-permalink-input]')

      function trimSlashes(value) {
        return String(value || '').trim().replace(/^\/+|\/+$/g, '')
      }

      function previewUrl(input) {
        var preview = document.getElementById(input.getAttribute('data-permalink-preview'))

        if (!preview) {
          return
        }

        var baseUrl = preview.getAttribute('data-base-url').replace(/\/+$/g, '')
        var value = input.getAttribute('data-permalink-type') === 'postfix'
          ? String(input.value || '').trim()
          : trimSlashes(input.value)
        var url = baseUrl

        if (input.getAttribute('data-permalink-type') === 'postfix') {
          url += '/your-url-here' + value
        } else {
          url += value ? '/' + value + '/your-url-here' : '/your-url-here'
        }

        preview.textContent = url
        preview.setAttribute('href', url)
      }

      inputs.forEach(function (input) {
        input.addEventListener('input', function () {
          previewUrl(input)
        })
        previewUrl(input)
      })
    })()
  </script>
@endpush
