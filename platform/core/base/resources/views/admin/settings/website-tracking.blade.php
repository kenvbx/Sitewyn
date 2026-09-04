@extends('core/base::admin.layouts.master')

@section('title', 'Website Tracking - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.settings.edit') }}">Settings</a></li>
  <li class="breadcrumb-item active" aria-current="page">Website Tracking</li>
@endsection

@section('content')
  @php($trackingType = old('website_tracking_type', $settings['website_tracking_type']))

  @if (session('status'))
    <div class="alert alert-success" role="alert">{{ session('status') }}</div>
  @endif

  <form id="website-tracking-settings-form" method="POST" action="{{ route('admin.settings.website-tracking.update', [], false) }}" class="needs-validation" data-admin-validate novalidate>
    @csrf
    @method('PUT')
    <input type="hidden" name="site_name" value="{{ $baseSettings['site_name'] }}">
    <input type="hidden" name="site_logo" value="{{ $baseSettings['site_logo'] }}">
    <input type="hidden" name="robots_txt" value="{{ $baseSettings['robots_txt'] }}">
    <input type="hidden" name="active_theme" value="{{ $baseSettings['active_theme'] }}">

    <div class="row mb-5 d-block d-md-flex">
      <div class="col-12 col-md-3">
        <h2>Website Tracking</h2>
        <p class="text-muted">Choose your preferred analytics and tracking method. Only one option can be active at a time.</p>
      </div>

      <div class="col-12 col-md-9">
        <div class="card">
          <div class="card-body">
            <div class="row row-cards mb-4">
              @foreach ($trackingTypeOptions as $value => $label)
                <div class="col-12 col-lg-4">
                  <label class="form-selectgroup-item flex-fill">
                    <input type="radio" name="website_tracking_type" value="{{ $value }}" class="form-selectgroup-input" @checked($trackingType === $value) data-website-tracking-toggle>
                    <span class="form-selectgroup-label d-flex align-items-center gap-2">
                      <span class="form-selectgroup-check"></span>
                      <span>{{ $label }}</span>
                    </span>
                  </label>
                </div>
              @endforeach
            </div>

            <div class="{{ $trackingType !== 'gtm' ? 'd-none' : '' }}" data-website-tracking-panel="gtm">
              <div class="alert alert-success" role="alert">
                <div class="d-flex">
                  <div>@include('core/base::admin.partials.icon', ['name' => 'info-circle'])</div>
                  <div class="ms-2">Best for managing multiple tracking services (Google Analytics, Facebook Pixel, etc.) in one place. Provides a user-friendly interface without coding.</div>
                </div>
              </div>

              <div class="card mb-4">
                <div class="card-header">
                  <h3 class="card-title">Setup Instructions</h3>
                </div>
                <div class="card-body">
                  <ol class="mb-0 ps-3">
                    <li><strong>Create GTM Account:</strong> Go to <a href="https://tagmanager.google.com" target="_blank" rel="noopener">tagmanager.google.com</a> and create a new container for your website</li>
                    <li><strong>Find Container ID:</strong> After creating, copy the Container ID (format: GTM-XXXXXXX) from the top right corner</li>
                    <li><strong>Paste ID Below:</strong> Enter your Container ID in the field below and save</li>
                    <li><strong>Configure Tags:</strong> Add your analytics tags (Google Analytics, Facebook Pixel, etc.) in the GTM dashboard</li>
                    <li><strong>Publish Container:</strong> Click "Submit" in GTM to publish your changes</li>
                  </ol>
                </div>
              </div>

              <div class="mb-4">
                <label class="form-label" for="website-tracking-gtm-container-id">GTM Container ID</label>
                <input type="text" name="website_tracking_gtm_container_id" id="website-tracking-gtm-container-id" value="{{ old('website_tracking_gtm_container_id', $settings['website_tracking_gtm_container_id']) }}" class="form-control" maxlength="30" placeholder="GTM-XXXXXXX">
                <div class="form-hint">Enter your GTM Container ID (e.g., GTM-NZMK3KH2). Find this in the top right corner of your Google Tag Manager dashboard.</div>
              </div>

              <div class="mb-4">
                <label class="form-check form-switch">
                  <input type="hidden" name="website_tracking_gtm_debug_mode" value="0">
                  <input type="checkbox" name="website_tracking_gtm_debug_mode" value="1" class="form-check-input" @checked(old('website_tracking_gtm_debug_mode', $settings['website_tracking_gtm_debug_mode']))>
                  <span class="form-check-label">Enable GTM Debug Mode</span>
                </label>
                <div class="form-hint ms-4">Enable debug mode to log GTM events to browser console and troubleshoot tracking issues. Disable in production for better performance.</div>
              </div>

              <div class="mb-4">
                <label class="form-check form-switch">
                  <input type="hidden" name="website_tracking_gtm_include_customer_data" value="0">
                  <input type="checkbox" name="website_tracking_gtm_include_customer_data" value="1" class="form-check-input" @checked(old('website_tracking_gtm_include_customer_data', $settings['website_tracking_gtm_include_customer_data']))>
                  <span class="form-check-label">Include customer data on purchase (Enhanced Conversions)</span>
                </label>
                <div class="form-hint ms-4">Adds a user_data object (email, phone, name, address) to the purchase dataLayer event for Google Ads Enhanced Conversions and Meta Advanced Matching. Values are sent unhashed for your GTM tags to hash. Only enable if your privacy policy and consent setup allow sharing this data.</div>
              </div>

              <div class="card mb-4">
                <div class="card-header">
                  <h3 class="card-title">Adding GA4 Tracking with GTM</h3>
                </div>
                <div class="card-body">
                  <p>After setting up your GTM container, add Google Analytics 4 tracking:</p>
                  <ol class="mb-0 ps-3">
                    <li>In GTM click Tags &gt; New &gt; Tag Configuration</li>
                    <li>Choose Google Analytics: GA4 Configuration</li>
                    <li>Enter your GA4 Measurement ID (get this from Google Analytics &gt; Admin &gt; Data Streams &gt; Web &gt; Measurement ID)</li>
                    <li>Set Trigger &gt; choose All Pages (so it fires on every page)</li>
                    <li>Save the tag and Publish your GTM container</li>
                  </ol>
                </div>
              </div>

              <div class="card mb-0">
                <div class="card-header">
                  <h3 class="card-title">How to Verify Your Setup</h3>
                </div>
                <div class="card-body">
                  <ol class="mb-4 ps-3">
                    <li><strong>Save Configuration:</strong> Click Save Changes button below</li>
                    <li><strong>Visit Your Website:</strong> Open your website in a new incognito/private window</li>
                    <li><strong>Check GTM Preview Mode:</strong> In GTM dashboard, click "Preview" to enable debugging</li>
                    <li><strong>Verify Tag Assistant:</strong> Install <a href="https://tagassistant.google.com" target="_blank" rel="noopener">Google Tag Assistant</a> Chrome extension to verify tags are firing</li>
                    <li><strong>Check Browser Console:</strong> Press F12, go to Console tab, look for GTM messages</li>
                  </ol>

                  <div class="alert alert-danger mb-0" role="alert">
                    <h4 class="alert-title">Common Issues:</h4>
                    <ul class="mb-0">
                      <li><strong>Tags Not Firing:</strong> Make sure you published your container in GTM</li>
                      <li><strong>Wrong Container:</strong> Verify you're using the correct Container ID for this domain</li>
                      <li><strong>Blocked by Ad Blocker:</strong> Test in incognito mode without extensions</li>
                      <li><strong>Caching Issues:</strong> Clear browser cache or test in incognito mode</li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>

            <div class="{{ $trackingType !== 'ga' ? 'd-none' : '' }}" data-website-tracking-panel="ga">
              <div class="alert alert-info" role="alert">
                <div class="d-flex">
                  <div>@include('core/base::admin.partials.icon', ['name' => 'info-circle'])</div>
                  <div class="ms-2">Simple setup for basic Google Analytics tracking. Choose this if you only need Google Analytics and nothing else.</div>
                </div>
              </div>

              <div class="card mb-4">
                <div class="card-header">
                  <h3 class="card-title">Setup Instructions</h3>
                </div>
                <div class="card-body">
                  <ol class="mb-4 ps-3">
                    <li><strong>Create GA4 Property:</strong> Go to <a href="https://analytics.google.com" target="_blank" rel="noopener">Google Analytics</a> and create a new GA4 property</li>
                    <li><strong>Find Measurement ID:</strong> Navigate to Admin &gt; Data Streams &gt; Select your stream &gt; Copy the Measurement ID (format: G-XXXXXXXXXX)</li>
                    <li><strong>Paste ID Below:</strong> Enter your Measurement ID in the field below and save</li>
                    <li><strong>Verify Setup:</strong> Check "Realtime" report in Google Analytics after saving</li>
                  </ol>

                  <div class="alert alert-warning mb-0" role="alert">
                    <h4 class="alert-title">Common Mistakes:</h4>
                    <ul class="mb-0">
                      <li>Using Property ID instead of Measurement ID</li>
                      <li>Using old Universal Analytics ID (UA-XXXXX-X) - these are deprecated</li>
                      <li>Not waiting 24-48 hours for data to appear in reports</li>
                    </ul>
                  </div>
                </div>
              </div>

              <div class="mb-4">
                <label class="form-label" for="website-tracking-ga-measurement-id">Google Analytics Only</label>
                <input type="text" name="website_tracking_ga_measurement_id" id="website-tracking-ga-measurement-id" value="{{ old('website_tracking_ga_measurement_id', $settings['website_tracking_ga_measurement_id']) }}" class="form-control" maxlength="30" placeholder="G-XXXXXXXXXX">
                <div class="form-hint">Enter your Google Analytics 4 Measurement ID. Find this in Google Analytics under Admin &gt; Data Streams &gt; Your Stream &gt; Measurement ID.</div>
              </div>

              <div class="card mb-0">
                <div class="card-header">
                  <h3 class="card-title">How to Verify Your Setup</h3>
                </div>
                <div class="card-body">
                  <ol class="mb-4 ps-3">
                    <li><strong>Save Configuration:</strong> Click Save Changes button below</li>
                    <li><strong>Visit Your Website:</strong> Open your website in a new incognito/private window</li>
                    <li><strong>Check Realtime Report:</strong> Go to Google Analytics &gt; Reports &gt; Realtime &gt; Overview</li>
                    <li><strong>Wait for Data:</strong> You should see your visit within 30 seconds</li>
                    <li><strong>Check Browser Console:</strong> Press F12, Network tab, filter by "google-analytics" to see if requests are sent</li>
                  </ol>

                  <div class="alert alert-danger mb-0" role="alert">
                    <h4 class="alert-title">Common Issues:</h4>
                    <ul class="mb-0">
                      <li><strong>No Data Appearing:</strong> Wait 24-48 hours for full reports (Realtime works immediately)</li>
                      <li><strong>Wrong ID Format:</strong> Must be G-XXXXXXXXXX not UA-XXXXX-X</li>
                      <li><strong>Blocked by Ad Blocker:</strong> Test in incognito mode without extensions</li>
                      <li><strong>Multiple IDs:</strong> Only use one ID field - GA4 Measurement ID (starts with G-)</li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>

            <div class="{{ $trackingType !== 'custom' ? 'd-none' : '' }}" data-website-tracking-panel="custom">
              <div class="alert alert-warning" role="alert">
                <div class="d-flex">
                  <div>@include('core/base::admin.partials.icon', ['name' => 'info-circle'])</div>
                  <div class="ms-2">For advanced users who need to add custom tracking scripts (Matomo, Plausible, Fathom, etc.) or have specific requirements.</div>
                </div>
              </div>

              <div class="card mb-4">
                <div class="card-header">
                  <h3 class="card-title">Setup Instructions</h3>
                </div>
                <div class="card-body">
                  <p class="fw-bold">Popular Analytics Services:</p>
                  <ul class="mb-4">
                    <li><strong>Matomo:</strong> Copy the JavaScript tracking code from Settings &gt; Tracking Code</li>
                    <li><strong>Plausible:</strong> Copy the script tag from Settings &gt; General</li>
                    <li><strong>Fathom:</strong> Get your site code from Settings &gt; Sites</li>
                    <li><strong>Facebook Pixel:</strong> Copy the base code from Events Manager</li>
                    <li><strong>Hotjar:</strong> Copy the tracking code from Settings &gt; Sites &amp; Organizations</li>
                  </ul>

                  <div class="alert alert-info mb-0" role="alert">
                    <h4 class="alert-title">Best Practices:</h4>
                    <ul class="mb-0">
                      <li>Most services provide a two-part installation: Header script + Optional body code</li>
                      <li>Always paste the complete code including &lt;script&gt; tags</li>
                      <li>Test in incognito mode after saving to verify tracking works</li>
                      <li>Use browser console (F12) to check for JavaScript errors</li>
                    </ul>
                  </div>
                </div>
              </div>

              <p class="text-muted">Add your tracking scripts following the standard two-part installation pattern used by most analytics services.</p>

              <div class="mb-4">
                <label class="form-label" for="website-tracking-custom-header-script">Step 1: Header tracking script</label>
                <textarea name="website_tracking_custom_header_script" id="website-tracking-custom-header-script" rows="12" class="form-control font-monospace" placeholder="<script>">{{ old('website_tracking_custom_header_script', $settings['website_tracking_custom_header_script']) }}</textarea>
                <div class="form-hint">Paste the tracking script that goes in the &lt;head&gt; section of your pages. Include the complete code with &lt;script&gt; tags.</div>
              </div>

              <div class="mb-4">
                <label class="form-label" for="website-tracking-custom-body-code">Step 2: Body tracking code (Optional)</label>
                <textarea name="website_tracking_custom_body_code" id="website-tracking-custom-body-code" rows="12" class="form-control font-monospace" placeholder="<noscript>">{{ old('website_tracking_custom_body_code', $settings['website_tracking_custom_body_code']) }}</textarea>
                <div class="form-hint">Paste any noscript or additional code that goes after the opening &lt;body&gt; tag. Leave empty if not required.</div>
              </div>

              <div class="card mb-0">
                <div class="card-header">
                  <h3 class="card-title">How to Verify Your Setup</h3>
                </div>
                <div class="card-body">
                  <ol class="mb-4 ps-3">
                    <li><strong>Save Configuration:</strong> Click Save Changes button below</li>
                    <li><strong>Visit Your Website:</strong> Open your website in a new incognito/private window</li>
                    <li><strong>Check Browser Console:</strong> Press F12, go to Console tab, look for your tracking service messages</li>
                    <li><strong>Verify Network Requests:</strong> In F12 Network tab, look for requests to your analytics provider</li>
                    <li><strong>Check Provider Dashboard:</strong> Most services have a realtime or debug view</li>
                  </ol>

                  <div class="alert alert-danger mb-0" role="alert">
                    <h4 class="alert-title">Common Issues:</h4>
                    <ul class="mb-0">
                      <li><strong>JavaScript Errors:</strong> Check browser console for syntax errors in your code</li>
                      <li><strong>Missing Script Tags:</strong> Ensure your code includes &lt;script&gt; and &lt;/script&gt; tags</li>
                      <li><strong>Incomplete Code:</strong> Copy the entire tracking code from your provider, not just part of it</li>
                      <li><strong>Wrong Placement:</strong> Header script goes in header field, body code goes in body field</li>
                      <li><strong>Quotes Issues:</strong> Make sure quotes in your code are properly formatted</li>
                    </ul>
                  </div>
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
      <button type="submit" class="btn btn-primary btn-lg" form="website-tracking-settings-form">
        @include('core/base::admin.partials.icon', ['name' => 'save'])
        <span class="ms-2">Save settings</span>
      </button>
    </div>
  </div>
@endsection

@push('scripts')
  <script>
    ;(function () {
      var toggles = document.querySelectorAll('[data-website-tracking-toggle]')
      var panels = document.querySelectorAll('[data-website-tracking-panel]')

      function syncPanels() {
        var checked = document.querySelector('[data-website-tracking-toggle]:checked')
        var active = checked ? checked.value : 'gtm'

        panels.forEach(function (panel) {
          panel.classList.toggle('d-none', panel.dataset.websiteTrackingPanel !== active)
        })
      }

      toggles.forEach(function (toggle) {
        toggle.addEventListener('change', syncPanels)
      })

      syncPanels()
    })()
  </script>
@endpush
