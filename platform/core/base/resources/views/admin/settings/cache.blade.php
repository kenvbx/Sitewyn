@extends('core/base::admin.layouts.master')

@section('title', 'Cache - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.settings.edit') }}">Settings</a></li>
  <li class="breadcrumb-item active" aria-current="page">Cache</li>
@endsection

@section('content')
  @php($shortcodesEnabled = old('cache_shortcodes', $settings['cache_shortcodes']))
  @php($widgetsEnabled = old('cache_widgets', $settings['cache_widgets']))
  @php($sitemapEnabled = old('cache_sitemap', $settings['cache_sitemap']))
  @php($publicHeadersEnabled = old('cache_public_headers', $settings['cache_public_headers']))

  @if (session('status'))
    <div class="alert alert-success" role="alert">{{ session('status') }}</div>
  @endif

  <form id="cache-settings-form" method="POST" action="{{ route('admin.settings.cache.update', [], false) }}" class="needs-validation" data-admin-validate novalidate>
    @csrf
    @method('PUT')
    <input type="hidden" name="site_name" value="{{ $baseSettings['site_name'] }}">
    <input type="hidden" name="site_logo" value="{{ $baseSettings['site_logo'] }}">
    <input type="hidden" name="robots_txt" value="{{ $baseSettings['robots_txt'] }}">
    <input type="hidden" name="active_theme" value="{{ $baseSettings['active_theme'] }}">

    <div class="row mb-5 d-block d-md-flex">
      <div class="col-12 col-md-3">
        <h2>Cache</h2>
        <p class="text-muted">Configure caching for optimized speed</p>
      </div>

      <div class="col-12 col-md-9">
        <div class="card">
          <div class="card-body">
            <div class="mb-4">
              <label class="form-check">
                <input type="hidden" name="cache_admin_menu" value="0">
                <input type="checkbox" name="cache_admin_menu" value="1" class="form-check-input" @checked(old('cache_admin_menu', $settings['cache_admin_menu']))>
                <span class="form-check-label">Cache admin menu</span>
              </label>
              <div class="form-hint ms-4">Cache admin menu for optimized speed. This option should be disabled if you are developing or customizing the admin menu.</div>
            </div>

            <div class="mb-4">
              <label class="form-check">
                <input type="hidden" name="cache_front_menu" value="0">
                <input type="checkbox" name="cache_front_menu" value="1" class="form-check-input" @checked(old('cache_front_menu', $settings['cache_front_menu']))>
                <span class="form-check-label">Cache front menu</span>
              </label>
              <div class="form-hint ms-4">Cache front menu for optimized speed.</div>
            </div>

            <div class="mb-4">
              <label class="form-check">
                <input type="hidden" name="cache_user_avatar" value="0">
                <input type="checkbox" name="cache_user_avatar" value="1" class="form-check-input" @checked(old('cache_user_avatar', $settings['cache_user_avatar']))>
                <span class="form-check-label">Cache user avatar</span>
              </label>
              <div class="form-hint ms-4">Generate and cache avatar images for users without a custom avatar so repeated requests stay fast.</div>
            </div>

            <div class="mb-4">
              <label class="form-check">
                <input type="hidden" name="cache_shortcodes" value="0">
                <input type="checkbox" name="cache_shortcodes" value="1" class="form-check-input" @checked($shortcodesEnabled) data-cache-toggle="shortcodes">
                <span class="form-check-label">Cache shortcodes (UI blocks)</span>
              </label>
              <div class="form-hint ms-4">Cache rendered shortcode and UI block output to reduce rendering time.</div>
            </div>

            <div class="{{ ! $shortcodesEnabled ? 'd-none' : '' }}" data-cache-panel="shortcodes">
              <div class="alert alert-warning" role="alert">
                <div class="d-flex">
                  <div>@include('core/base::admin.partials.icon', ['name' => 'alert-circle'])</div>
                  <div class="ms-2">
                    <h4 class="alert-title">Important Notice</h4>
                    <div>
                      <ul class="mb-0 ps-3">
                        <li>Shortcode output is cached until the cache duration expires.</li>
                        <li>Clear cache after changing shortcode templates, UI blocks, or related settings.</li>
                        <li>Disable this option while developing or customizing shortcode rendering.</li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>

              <div class="mb-4">
                <label class="form-label" for="cache-shortcodes-duration">Cache duration (seconds)</label>
                <input type="number" name="cache_shortcodes_duration" id="cache-shortcodes-duration" value="{{ old('cache_shortcodes_duration', $settings['cache_shortcodes_duration']) }}" class="form-control" min="0" max="86400" step="1">
                <div class="form-hint">Default is 1800 seconds, equal to 30 minutes.</div>
              </div>
            </div>

            <div class="mb-4">
              <label class="form-check">
                <input type="hidden" name="cache_widgets" value="0">
                <input type="checkbox" name="cache_widgets" value="1" class="form-check-input" @checked($widgetsEnabled) data-cache-toggle="widgets">
                <span class="form-check-label">Cache widgets</span>
              </label>
              <div class="form-hint ms-4">Cache rendered widgets to reduce rendering time.</div>
            </div>

            <div class="{{ ! $widgetsEnabled ? 'd-none' : '' }}" data-cache-panel="widgets">
              <div class="alert alert-warning" role="alert">
                <div class="d-flex">
                  <div>@include('core/base::admin.partials.icon', ['name' => 'alert-circle'])</div>
                  <div class="ms-2">
                    <h4 class="alert-title">Important Notice</h4>
                    <div>
                      <ul class="mb-0 ps-3">
                        <li>Widget output is cached until the cache duration expires.</li>
                        <li>Clear cache after changing widget templates, widget content, or related settings.</li>
                        <li>Disable this option while developing or customizing widget rendering.</li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>

              <div class="mb-4">
                <label class="form-label" for="cache-widgets-duration">Cache duration (seconds)</label>
                <input type="number" name="cache_widgets_duration" id="cache-widgets-duration" value="{{ old('cache_widgets_duration', $settings['cache_widgets_duration']) }}" class="form-control" min="0" max="86400" step="1">
                <div class="form-hint">Default is 1800 seconds, equal to 30 minutes.</div>
              </div>
            </div>

            <div class="mb-4">
              <label class="form-check">
                <input type="hidden" name="cache_installed_plugins" value="0">
                <input type="checkbox" name="cache_installed_plugins" value="1" class="form-check-input" @checked(old('cache_installed_plugins', $settings['cache_installed_plugins']))>
                <span class="form-check-label">Cache installed plugins</span>
              </label>
              <div class="form-hint ms-4">Cache plugin list for 30 minutes to reduce filesystem scanning.</div>
            </div>

            <div class="mb-4">
              <label class="form-label" for="cache-size-warning-threshold">Cache size warning threshold (MB)</label>
              <input type="number" name="cache_size_warning_threshold" id="cache-size-warning-threshold" value="{{ old('cache_size_warning_threshold', $settings['cache_size_warning_threshold']) }}" class="form-control" min="1" max="10240" step="1">
              <div class="form-hint">Show a warning on the Cache Management page when framework cache size exceeds this value.</div>
            </div>

            <div class="mb-4">
              <label class="form-check">
                <input type="hidden" name="cache_auto_clear_when_size_exceeds_threshold" value="0">
                <input type="checkbox" name="cache_auto_clear_when_size_exceeds_threshold" value="1" class="form-check-input" @checked(old('cache_auto_clear_when_size_exceeds_threshold', $settings['cache_auto_clear_when_size_exceeds_threshold']))>
                <span class="form-check-label">Auto-clear cache when size exceeds threshold</span>
              </label>
              <div class="form-hint ms-4">Requires Laravel scheduler to run regularly via <code>php artisan schedule:run</code>.</div>
            </div>

            <div class="mb-4">
              <label class="form-check">
                <input type="hidden" name="cache_sitemap" value="0">
                <input type="checkbox" name="cache_sitemap" value="1" class="form-check-input" @checked($sitemapEnabled) data-cache-toggle="sitemap">
                <span class="form-check-label">Cache sitemap</span>
              </label>
              <div class="form-hint ms-4">Cache sitemap response for <a href="{{ $sitemapUrl }}" target="_blank" rel="noopener">{{ $sitemapUrl }}</a>.</div>
            </div>

            <div class="{{ ! $sitemapEnabled ? 'd-none' : '' }}" data-cache-panel="sitemap">
              <div class="mb-4">
                <label class="form-label" for="cache-sitemap-timeout">Sitemap cache timeout (in minutes)</label>
                <input type="number" name="cache_sitemap_timeout" id="cache-sitemap-timeout" value="{{ old('cache_sitemap_timeout', $settings['cache_sitemap_timeout']) }}" class="form-control" min="1" max="10080" step="1">
              </div>
            </div>

            <div class="mb-4">
              <label class="form-check">
                <input type="hidden" name="cache_public_headers" value="0">
                <input type="checkbox" name="cache_public_headers" value="1" class="form-check-input" @checked($publicHeadersEnabled) data-cache-toggle="public-headers">
                <span class="form-check-label">Public cache headers (CDN / reverse proxy)</span>
              </label>
              <div class="form-hint ms-4">Send <code>Cache-Control: public</code> for guest views, excluding admin, logged-in sessions, CSRF responses, <code>APP_DEBUG</code>, and requests marked with <code>X-Public-Cache-Skip</code>.</div>
            </div>

            <div class="{{ ! $publicHeadersEnabled ? 'd-none' : '' }}" data-cache-panel="public-headers">
              <div class="mb-0">
                <label class="form-label" for="cache-public-duration">Public cache duration (seconds)</label>
                <input type="number" name="cache_public_duration" id="cache-public-duration" value="{{ old('cache_public_duration', $settings['cache_public_duration']) }}" class="form-control" min="0" max="86400" step="1">
                <div class="form-hint">Default is 120 seconds for fast-moving content.</div>
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
      <button type="submit" class="btn btn-primary btn-lg" form="cache-settings-form">
        @include('core/base::admin.partials.icon', ['name' => 'save'])
        <span class="ms-2">Save settings</span>
      </button>
    </div>
  </div>
@endsection

@push('scripts')
  <script>
    ;(function () {
      function syncPanel(toggle) {
        var panel = document.querySelector('[data-cache-panel="' + toggle.dataset.cacheToggle + '"]')

        if (panel) {
          panel.classList.toggle('d-none', !toggle.checked)
        }
      }

      document.querySelectorAll('[data-cache-toggle]').forEach(function (toggle) {
        toggle.addEventListener('change', function () {
          syncPanel(toggle)
        })

        syncPanel(toggle)
      })
    })()
  </script>
@endpush
