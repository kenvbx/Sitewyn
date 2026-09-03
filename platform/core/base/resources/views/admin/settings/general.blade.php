@extends('core/base::admin.layouts.master')

@section('title', 'General Settings - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.settings.edit') }}">Settings</a></li>
  <li class="breadcrumb-item active" aria-current="page">General</li>
@endsection

@section('content')
  <form method="POST" action="{{ route('admin.settings.update', [], false) }}" class="needs-validation" data-admin-validate novalidate>
        @csrf
        @method('PUT')
        <input type="hidden" name="_redirect" value="{{ route('admin.settings.general', [], false) }}">
        <input type="hidden" name="site_name" value="{{ $settings['site_name'] }}">
        <input type="hidden" name="site_logo" value="{{ $settings['site_logo'] }}">
        <input type="hidden" name="robots_txt" value="{{ $settings['robots_txt'] }}">
        <input type="hidden" name="active_theme" value="{{ $settings['active_theme'] }}">

        <div class="row mb-5 d-block d-md-flex">
            <div class="col-12 col-md-3">
                <h2>License</h2>
                <p class="text-muted">Setup license code</p>
            </div>

            <div class="col-12 col-md-9">
                <div class="card">
                <div class="card-body">
                    <p class="text-primary">Licensed to {{ config('app.name', 'Sitewyn') }}. Activated since {{ now()->format('M d Y') }}.</p>
                    <button type="button" class="btn btn-warning btn-lg text-white">
                        @include('core/base::admin.partials.icon', ['name' => 'alert-circle'])
                        <span class="ms-2">Deactivate license</span>
                    </button>
                </div>
                </div>
            </div>
        </div>

    <div class="row mb-5 d-block d-md-flex">
      <div class="col-12 col-md-3">
        <h2>General Information</h2>
        <p class="text-muted">View and update site information</p>
      </div>

      <div class="col-12 col-md-9">
        <div class="card">
          <div class="card-body">
            <div class="mb-3" data-admin-emails>
              <div class="d-flex align-items-center justify-content-between mb-2">
                <label class="form-label mb-0">Admin Email</label>
                <button type="button" class="btn btn-link px-0" data-admin-email-add>+ Add more</button>
              </div>

              <div data-admin-email-list>
                @foreach ($settings['admin_emails'] as $email)
                  <input type="email"
                         name="admin_emails[]"
                         class="form-control mb-2"
                         value="{{ $email }}"
                         maxlength="255"
                         @if ($loop->first) required @endif
                         autocomplete="email"
                         placeholder="admin@example.com">
                @endforeach
              </div>

              <div class="form-hint">You can add maximum 4 emails</div>
            </div>

            <div class="mb-3">
              <label class="form-label" for="settings-timezone">Timezone</label>
              <select name="timezone" id="settings-timezone" class="form-select">
                @foreach ($timezoneOptions as $value => $label)
                  <option value="{{ $value }}" @selected($settings['timezone'] === $value)>{{ $label }}</option>
                @endforeach
              </select>
              <div class="form-hint">Select the timezone for your website. This will be used for all date and time displays throughout the system</div>
            </div>

            <div class="mb-4">
              <label class="form-label">Front site language direction</label>
              <div class="form-selectgroup">
                <label class="form-selectgroup-item">
                  <input type="radio" name="front_site_language_direction" value="ltr" class="form-selectgroup-input" @checked($settings['front_site_language_direction'] === 'ltr')>
                  <span class="form-selectgroup-label">Left to Right</span>
                </label>
                <label class="form-selectgroup-item">
                  <input type="radio" name="front_site_language_direction" value="rtl" class="form-selectgroup-input" @checked($settings['front_site_language_direction'] === 'rtl')>
                  <span class="form-selectgroup-label">Right to Left</span>
                </label>
              </div>
            </div>

            <p class="text-secondary">
              To set up the site language, please go to
              <a href="{{ route('admin.settings.languages.index', [], false) }}">Languages</a> page.
              Or go to <a href="{{ route('admin.settings.edit', [], false) }}">Appearance</a> to set up the admin language.
            </p>

            <div class="mb-3">
              <label class="form-label" for="settings-site-language">Site language</label>
              <select name="site_language" id="settings-site-language" class="form-select">
                @foreach ($languageOptions as $value => $label)
                  <option value="{{ $value }}" @selected($settings['site_language'] === $value)>{{ $label }}</option>
                @endforeach
              </select>
            </div>

            <div class="mb-3">
              <label class="form-check form-switch">
                <input type="hidden" name="send_error_reporting_via_email" value="0">
                <input class="form-check-input" type="checkbox" name="send_error_reporting_via_email" value="1" @checked($settings['send_error_reporting_via_email'])>
                <span class="form-check-label">Send error reporting via email</span>
              </label>
              <div class="form-hint">When enabled, detailed error reports will be sent to the admin email addresses when critical errors occur on the site</div>
            </div>

            <div class="mb-3">
              <label class="form-check">
                <input type="hidden" name="redirect_404_to_homepage" value="0">
                <input class="form-check-input" type="checkbox" name="redirect_404_to_homepage" value="1" @checked($settings['redirect_404_to_homepage'])>
                <span class="form-check-label">Redirect all Not Found requests to homepage</span>
              </label>
            </div>

            <div class="mb-3">
              <label class="form-label" for="settings-clear-request-logs">Clear old Request Logs</label>
              <select name="clear_old_request_logs" id="settings-clear-request-logs" class="form-select">
                @foreach ($logRetentionOptions as $value => $label)
                  <option value="{{ $value }}" @selected($settings['clear_old_request_logs'] === $value)>{{ $label }}</option>
                @endforeach
              </select>
              <div class="form-hint">Automatically delete old request logs that are older than the selected period to keep your database clean and optimized.</div>
            </div>

            <div class="alert alert-warning" role="alert">
              <div class="d-flex gap-2">
                <div>@include('core/base::admin.partials.icon', ['name' => 'alert-circle'])</div>
                <div>To use this feature, you need to set up a cron job by following this link: <a href="{{ $cronjobUrl }}">{{ $cronjobUrl }}</a>.</div>
              </div>
            </div>

            <div class="mb-0">
              <label class="form-label" for="settings-clear-audit-logs">Clear old Audit Logs</label>
              <select name="clear_old_audit_logs" id="settings-clear-audit-logs" class="form-select">
                @foreach ($logRetentionOptions as $value => $label)
                  <option value="{{ $value }}" @selected($settings['clear_old_audit_logs'] === $value)>{{ $label }}</option>
                @endforeach
              </select>
              <div class="form-hint">Automatically delete audit logs that are older than the selected period to keep your database clean and optimized.</div>
            </div>
          </div>
        </div>

        <div class="mt-4">
          <button type="submit" class="btn btn-primary btn-lg">
            @include('core/base::admin.partials.icon', ['name' => 'settings'])
            <span class="ms-2">Save settings</span>
          </button>
        </div>
      </div>
    </div>
  </form>
@endsection

@push('scripts')
  <script>
    document.querySelectorAll('[data-admin-emails]').forEach(function (container) {
      var addButton = container.querySelector('[data-admin-email-add]')
      var list = container.querySelector('[data-admin-email-list]')

      if (!addButton || !list) {
        return
      }

      addButton.addEventListener('click', function () {
        if (list.querySelectorAll('input[name="admin_emails[]"]').length >= 4) {
          return
        }

        var input = document.createElement('input')

        input.type = 'email'
        input.name = 'admin_emails[]'
        input.className = 'form-control mb-2'
        input.maxLength = 255
        input.autocomplete = 'email'
        input.placeholder = 'admin@example.com'
        list.appendChild(input)
        input.focus()
      })
    })
  </script>
@endpush
