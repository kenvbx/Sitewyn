@extends('core/base::admin.layouts.master')

@section('title', 'API settings - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.settings.edit') }}">Settings</a></li>
  <li class="breadcrumb-item active" aria-current="page">API settings</li>
@endsection

@push('styles')
  <style>
    .sitewyn-api-code {
      display: block;
      margin: 0;
      padding: 1rem 1.25rem;
      border-radius: var(--tblr-border-radius);
      background: #1f2937;
      color: #f8fafc;
      overflow-x: auto;
      font-size: .875rem;
      line-height: 1.7;
      white-space: pre;
    }

    .sitewyn-api-documentation {
      border-color: rgba(var(--tblr-primary-rgb), .24) !important;
      background: rgba(var(--tblr-primary-rgb), .04);
    }
  </style>
@endpush

@section('content')
  @php($apiEnabled = old('api_enabled', $settings['api_enabled']))
  @php($pushEnabled = old('api_push_notifications_enabled', $settings['api_push_notifications_enabled']))

  @if (session('status'))
    <div class="alert alert-success" role="alert">{{ session('status') }}</div>
  @endif

  <form id="api-settings-form" method="POST" action="{{ route('admin.settings.api.update', [], false) }}" class="needs-validation" data-admin-validate novalidate>
    @csrf
    @method('PUT')
    <input type="hidden" name="site_name" value="{{ $baseSettings['site_name'] }}">
    <input type="hidden" name="site_logo" value="{{ $baseSettings['site_logo'] }}">
    <input type="hidden" name="robots_txt" value="{{ $baseSettings['robots_txt'] }}">
    <input type="hidden" name="active_theme" value="{{ $baseSettings['active_theme'] }}">

    <div class="row mb-5 d-block d-md-flex">
      <div class="col-12 col-md-3">
        <h2>API settings</h2>
        <p class="text-muted">Configure your API access and security settings</p>
      </div>

      <div class="col-12 col-md-9">
        <div class="card">
          <div class="card-body">
            <div class="mb-3">
              <label class="form-check">
                <input type="hidden" name="api_enabled" value="0">
                <input type="checkbox" name="api_enabled" value="1" class="form-check-input" @checked($apiEnabled) data-api-enabled-toggle>
                <span class="form-check-label">Enable API</span>
              </label>
              <div class="form-hint ms-4">Enable or disable the REST API for your website. When disabled, all API endpoints will be inaccessible.</div>
            </div>

            <div class="{{ ! $apiEnabled ? 'd-none' : '' }}" data-api-settings-panel>
              <div class="border rounded p-4 mb-4">
                <h3 class="card-title">Security Settings</h3>
                <p class="text-muted">The API key acts as a shared secret between your server and clients (e.g., your mobile app). It prevents unknown clients from accessing the API. User identity is still handled separately by Sanctum tokens.</p>

                <div class="mb-3">
                  <label class="form-label" for="api-key">API Key</label>
                  <div class="input-group">
                    <input type="text" name="api_key" id="api-key" value="{{ old('api_key', $settings['api_key']) }}" class="form-control" maxlength="255" placeholder="Enter your API key (leave empty to disable)" autocomplete="off" data-api-key-input>
                    <button type="button" class="btn btn-outline-primary" data-api-key-generate>
                      @include('core/base::admin.partials.icon', ['name' => 'reload'])
                      <span class="ms-2">Generate Random Key</span>
                    </button>
                  </div>
                  <div class="form-hint">Optional security key for API access. When set, all API requests must include this key in the X-API-KEY header.</div>
                  <div class="text-warning mt-3 @if (old('api_key', $settings['api_key'])) d-none @endif" data-api-key-warning>
                    @include('core/base::admin.partials.icon', ['name' => 'alert-circle'])
                    <span class="ms-2">API key protection is <strong>disabled</strong>. Any client can access the API without an API key.</span>
                  </div>
                </div>

                <div class="mb-3">
                  <h3 class="card-title">Push Notifications (FCM v1 API)</h3>
                  <p class="text-muted">Send push notifications to your mobile app users via Firebase Cloud Messaging. This requires a mobile app that registers device tokens with your API.</p>
                  <label class="form-check">
                    <input type="hidden" name="api_push_notifications_enabled" value="0">
                    <input type="checkbox" name="api_push_notifications_enabled" value="1" class="form-check-input" @checked($pushEnabled) data-api-push-toggle>
                    <span class="form-check-label">Enable Push Notifications</span>
                  </label>
                  <div class="form-hint ms-4">Enable or disable push notifications for mobile apps. When disabled, no notifications will be sent to devices.</div>
                </div>

                <div class="{{ ! $pushEnabled ? 'd-none' : '' }}" data-api-push-panel>
                  <div class="mb-3">
                    <label class="form-label" for="api-fcm-project-id">Firebase Project ID</label>
                    <input type="text" name="api_fcm_project_id" id="api-fcm-project-id" value="{{ old('api_fcm_project_id', $settings['api_fcm_project_id']) }}" class="form-control" maxlength="255" placeholder="your-firebase-project-id">
                  </div>

                  <div class="mb-4">
                    <label class="form-label" for="api-fcm-service-account-json">Service account JSON</label>
                    <textarea name="api_fcm_service_account_json" id="api-fcm-service-account-json" rows="8" class="form-control font-monospace" placeholder='{"type":"service_account", ...}'>{{ old('api_fcm_service_account_json', $settings['api_fcm_service_account_json']) }}</textarea>
                    <div class="form-hint">Paste the Firebase service account JSON used by the FCM v1 API.</div>
                  </div>
                </div>

                <div class="mb-4">
                  <h3 class="card-title">Help & Documentation</h3>
                  <h4 class="mb-3">API Documentation</h4>
                  <div class="sitewyn-api-documentation border rounded p-4">
                    <div class="d-flex align-items-center mb-4">
                      @include('core/base::admin.partials.icon', ['name' => 'info-circle'])
                      <strong class="ms-2">Generate API Documentation</strong>
                    </div>
                    <p>To generate API documentation for your CMS application, follow these simple steps:</p>

                    <p class="fw-bold mb-2">1. Install the Scribe package:</p>
                    <pre class="sitewyn-api-code mb-3"><code>composer require knuckleswtf/scribe</code></pre>

                    <p class="fw-bold mb-2">2. Generate the API documentation:</p>
                    <pre class="sitewyn-api-code mb-3"><code>php artisan scribe:generate</code></pre>

                    <p class="fw-bold mb-2">3. Access your API documentation at:</p>
                    <a href="{{ $apiDocsUrl }}" class="text-decoration-none">{{ $apiDocsUrl }}</a>
                  </div>
                </div>

                <div class="mb-0">
                  <h4>Usage Examples</h4>
                  <p class="mb-2">Example cURL request with API key:</p>
                  <pre class="sitewyn-api-code mb-4"><code>curl -X GET "{{ $apiBaseUrl }}/pages" \
    -H "Accept: application/json" \
    -H "X-API-KEY: your-api-key-here"</code></pre>

                  <p class="mb-2">Example JavaScript request:</p>
                  <pre class="sitewyn-api-code"><code>fetch("{{ $apiBaseUrl }}/pages", {
    method: "GET",
    headers: {
        "Accept": "application/json",
        "X-API-KEY": "your-api-key-here"
    }
})
.then(response => response.json())
.then(data => console.log(data));</code></pre>
                </div>
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
      <button type="submit" class="btn btn-primary btn-lg" form="api-settings-form">
        @include('core/base::admin.partials.icon', ['name' => 'save'])
        <span class="ms-2">Save settings</span>
      </button>
    </div>
  </div>
@endsection

@push('scripts')
  <script>
    ;(function () {
      var apiToggle = document.querySelector('[data-api-enabled-toggle]')
      var apiPanel = document.querySelector('[data-api-settings-panel]')
      var pushToggle = document.querySelector('[data-api-push-toggle]')
      var pushPanel = document.querySelector('[data-api-push-panel]')
      var keyInput = document.querySelector('[data-api-key-input]')
      var keyGenerate = document.querySelector('[data-api-key-generate]')
      var keyWarning = document.querySelector('[data-api-key-warning]')

      function syncPanels() {
        if (apiPanel && apiToggle) {
          apiPanel.classList.toggle('d-none', !apiToggle.checked)
        }

        if (pushPanel && pushToggle) {
          pushPanel.classList.toggle('d-none', !pushToggle.checked)
        }

        if (keyWarning && keyInput) {
          keyWarning.classList.toggle('d-none', keyInput.value.trim() !== '')
        }
      }

      function randomKey() {
        var bytes = new Uint8Array(32)

        if (window.crypto && window.crypto.getRandomValues) {
          window.crypto.getRandomValues(bytes)
        } else {
          bytes = bytes.map(function () {
            return Math.floor(Math.random() * 256)
          })
        }

        return Array.from(bytes, function (byte) {
          return byte.toString(16).padStart(2, '0')
        }).join('')
      }

      apiToggle && apiToggle.addEventListener('change', syncPanels)
      pushToggle && pushToggle.addEventListener('change', syncPanels)
      keyInput && keyInput.addEventListener('input', syncPanels)
      keyGenerate && keyGenerate.addEventListener('click', function () {
        if (keyInput) {
          keyInput.value = randomKey()
          keyInput.dispatchEvent(new Event('input', { bubbles: true }))
          keyInput.focus()
        }
      })

      syncPanels()
    })()
  </script>
@endpush
