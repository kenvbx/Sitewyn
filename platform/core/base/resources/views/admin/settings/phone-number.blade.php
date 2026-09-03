@extends('core/base::admin.layouts.master')

@section('title', 'Phone Number - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.settings.edit') }}">Settings</a></li>
  <li class="breadcrumb-item active" aria-current="page">Phone Number</li>
@endsection

@section('content')
  @if (session('status'))
    <div class="alert alert-success" role="alert">{{ session('status') }}</div>
  @endif

  <form method="POST" action="{{ route('admin.settings.phone-number.update', [], false) }}" class="needs-validation" data-admin-validate novalidate>
    @csrf
    @method('PUT')
    <input type="hidden" name="site_name" value="{{ $baseSettings['site_name'] }}">
    <input type="hidden" name="site_logo" value="{{ $baseSettings['site_logo'] }}">
    <input type="hidden" name="robots_txt" value="{{ $baseSettings['robots_txt'] }}">
    <input type="hidden" name="active_theme" value="{{ $baseSettings['active_theme'] }}">

    <div class="row mb-5 d-block d-md-flex">
      <div class="col-12 col-md-3">
        <h2>Phone Number</h2>
        <p class="text-muted">Configure phone number field settings</p>
      </div>

      <div class="col-12 col-md-9">
        <div class="card">
          <div class="card-body">
            <div class="mb-4">
              <label class="form-check">
                <input type="hidden" name="phone_number_enable_country_code" value="0">
                <input type="checkbox" name="phone_number_enable_country_code" value="1" class="form-check-input" @checked(old('phone_number_enable_country_code', $settings['phone_number_enable_country_code'])) data-phone-country-toggle>
                <span class="form-check-label">Enable Country Code Selection</span>
              </label>
              <div class="form-hint ms-4">When enabled, phone number fields will display a country code selector with automatic country detection.</div>
            </div>

            <div class="border rounded p-3 mb-4 @if (! old('phone_number_enable_country_code', $settings['phone_number_enable_country_code'])) d-none @endif"
                 data-phone-country-panel
                 data-phone-country-visible="{{ old('phone_number_enable_country_code', $settings['phone_number_enable_country_code']) ? '1' : '0' }}">
              <div class="mb-3">
                <label class="form-check">
                  <input type="hidden" name="phone_number_available_countries_all" value="0">
                  <input type="checkbox" name="phone_number_available_countries_all" value="1" class="form-check-input" @checked(old('phone_number_available_countries_all', $settings['phone_number_available_countries_all'])) data-phone-country-all>
                  <span class="form-check-label">All</span>
                </label>
                <div class="form-hint ms-4">Select all countries to be available in the phone country code selector.</div>
              </div>

              <label class="form-label">Available Countries</label>
              <div class="border rounded p-3 overflow-auto" style="max-height: 420px;" data-phone-country-list>
                @foreach ($countryOptions as $code => $country)
                  <label class="form-check mb-3">
                    <input type="checkbox"
                           name="phone_number_available_countries[]"
                           value="{{ $code }}"
                           class="form-check-input"
                           @checked(in_array($code, old('phone_number_available_countries', $settings['phone_number_available_countries']), true))
                           data-phone-country-item>
                    <span class="form-check-label">{{ $country }}</span>
                  </label>
                @endforeach
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label" for="phone-number-minimum-length">Minimum Length</label>
              <input type="number"
                     name="phone_number_minimum_length"
                     id="phone-number-minimum-length"
                     value="{{ old('phone_number_minimum_length', $settings['phone_number_minimum_length']) }}"
                     class="form-control"
                     min="1"
                     max="30">
              <div class="form-hint">Minimum number of characters required for phone numbers.</div>
            </div>

            <div class="mb-4">
              <label class="form-label" for="phone-number-maximum-length">Maximum Length</label>
              <input type="number"
                     name="phone_number_maximum_length"
                     id="phone-number-maximum-length"
                     value="{{ old('phone_number_maximum_length', $settings['phone_number_maximum_length']) }}"
                     class="form-control"
                     min="1"
                     max="30">
              <div class="form-hint">Maximum number of characters allowed for phone numbers.</div>
            </div>

            <div class="alert alert-info mb-0" role="alert">
              <div><strong>For local format (without country code):</strong> Set min/max based on your country's phone number length (e.g., 8-15 for most countries).</div>
              <div class="mt-2"><strong>For international format (with country code enabled):</strong> Recommended min: 7, max: 20. This accommodates country codes (1-4 digits) + phone numbers (typically 6-15 digits).</div>
            </div>
          </div>
        </div>

        <div class="mt-4">
          <button type="submit" class="btn btn-primary btn-lg">
            @include('core/base::admin.partials.icon', ['name' => 'save'])
            <span class="ms-2">Save settings</span>
          </button>
        </div>
      </div>
    </div>
  </form>
@endsection

@push('scripts')
  <script>
    document.querySelectorAll('[data-phone-country-toggle]').forEach(function (toggle) {
      var panel = document.querySelector('[data-phone-country-panel]')

      if (!panel) {
        return
      }

      var syncPanel = function () {
        panel.classList.toggle('d-none', !toggle.checked)
        panel.setAttribute('data-phone-country-visible', toggle.checked ? '1' : '0')
      }

      toggle.addEventListener('change', syncPanel)
      syncPanel()
    })

    document.querySelectorAll('[data-phone-country-panel]').forEach(function (panel) {
      var allCheckbox = panel.querySelector('[data-phone-country-all]')
      var countryCheckboxes = panel.querySelectorAll('[data-phone-country-item]')

      if (!allCheckbox) {
        return
      }

      allCheckbox.addEventListener('change', function () {
        countryCheckboxes.forEach(function (checkbox) {
          checkbox.checked = allCheckbox.checked
        })
      })

      countryCheckboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
          allCheckbox.checked = Array.from(countryCheckboxes).every(function (item) {
            return item.checked
          })
        })
      })
    })
  </script>
@endpush
