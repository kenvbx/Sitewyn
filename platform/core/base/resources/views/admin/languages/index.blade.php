@extends('core/base::admin.layouts.master')

@section('title', 'Languages - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.settings.edit') }}">Settings</a></li>
  <li class="breadcrumb-item active" aria-current="page">Languages</li>
@endsection

@push('styles')
  <link href="{{ asset('vendor/core-base/libraries/select2/css/select2.min.css') }}" rel="stylesheet" />
  <style>
    .sitewyn-select2 + .select2-container .select2-selection--single {
      min-height: 36px;
      border-color: var(--tblr-border-color);
      border-radius: var(--tblr-border-radius);
      background-color: var(--tblr-bg-forms);
    }

    .sitewyn-select2 + .select2-container .select2-selection--single .select2-selection__rendered {
      padding-left: .75rem;
      padding-right: 2rem;
      color: var(--tblr-body-color);
      line-height: 34px;
    }

    .sitewyn-select2 + .select2-container .select2-selection--single .select2-selection__arrow {
      height: 34px;
      right: .5rem;
    }

    .sitewyn-select2 + .select2-container.select2-container--focus .select2-selection--single,
    .sitewyn-select2 + .select2-container.select2-container--open .select2-selection--single {
      border-color: var(--tblr-primary);
      box-shadow: 0 0 0 .25rem rgba(var(--tblr-primary-rgb), .25);
    }

    .select2-dropdown.sitewyn-language-select2-dropdown {
      border-color: var(--tblr-border-color);
      border-radius: var(--tblr-border-radius);
      background: var(--tblr-bg-surface);
      color: var(--tblr-body-color);
    }

    .sitewyn-language-select2-dropdown .select2-search--dropdown {
      padding: .5rem;
    }

    .sitewyn-language-select2-dropdown .select2-search--dropdown .select2-search__field {
      min-height: 36px;
      border-color: var(--tblr-border-color);
      border-radius: var(--tblr-border-radius);
      background: var(--tblr-bg-forms);
      color: var(--tblr-body-color);
      outline: 0;
    }

    .sitewyn-language-select2-dropdown .select2-results__option {
      padding: .5rem .75rem;
    }

    .sitewyn-language-select2-dropdown .select2-results__option--highlighted[aria-selected] {
      background-color: var(--tblr-primary);
      color: #fff;
    }
  </style>
@endpush

@section('content')
  @if (session('status'))
    <div class="alert alert-success" role="alert">{{ session('status') }}</div>
  @endif

  @php
    $language = $editingLanguage;
    $formAction = $language
        ? route('admin.settings.languages.update', $language, false)
        : route('admin.settings.languages.store', [], false);
  @endphp

  <div class="card">
    <div class="card-header">
      <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs" role="tablist">
        <li class="nav-item" role="presentation">
          <a href="#languages-detail" class="nav-link @if ($activeTab === 'detail') active @endif" data-bs-toggle="tab" aria-selected="{{ $activeTab === 'detail' ? 'true' : 'false' }}" role="tab">Detail</a>
        </li>
        <li class="nav-item" role="presentation">
          <a href="#languages-settings" class="nav-link @if ($activeTab === 'settings') active @endif" data-bs-toggle="tab" aria-selected="{{ $activeTab === 'settings' ? 'true' : 'false' }}" role="tab">Settings</a>
        </li>
      </ul>
    </div>

    <div class="card-body">
      <div class="tab-content">
        <div class="tab-pane @if ($activeTab === 'detail') active show @endif" id="languages-detail" role="tabpanel">
          <div class="row g-4">
            <div class="col-12 col-lg-5">
              <form method="POST" action="{{ $formAction }}" class="needs-validation" data-admin-validate novalidate>
                @csrf
                @if ($language)
                  @method('PUT')
                @endif

                <div class="mb-3">
                  <label class="form-label" for="language-preset">Choose a language</label>
                  <select id="language-preset" class="form-select sitewyn-select2" data-language-preset data-admin-select2 data-placeholder="Select language">
                    <option value="">Select language</option>
                    @foreach ($languagePresets as $code => $preset)
                      <option
                        value="{{ $code }}"
                        data-name="{{ $preset['native_name'] }}"
                        data-locale="{{ $preset['locale'] }}"
                        data-flag="{{ $preset['flag'] }}"
                        data-direction="{{ $preset['text_direction'] }}"
                      >{{ $preset['name'] }}@if ($preset['native_name'] !== $preset['name']) - {{ $preset['native_name'] }}@endif</option>
                    @endforeach
                  </select>
                  <div class="form-hint">You can choose a language in the list or directly edit it below.</div>
                </div>

                <div class="mb-3">
                  <label class="form-label" for="language-name">Language name</label>
                  <input type="text" name="name" id="language-name" value="{{ old('name', $language?->name) }}" class="form-control" maxlength="255" required data-language-name>
                  <div class="form-hint">The name is how it is displayed on your site (for example: English).</div>
                </div>

                <div class="mb-3">
                  <label class="form-label" for="language-locale">Locale</label>
                  <select name="locale" id="language-locale" class="form-select sitewyn-select2" required data-language-locale data-admin-select2 data-placeholder="Select locale">
                    <option value="">Select locale</option>
                    @foreach ($localeOptions as $value => $label)
                      <option value="{{ $value }}" @selected(old('locale', $language?->locale) === $value)>{{ $label }}</option>
                    @endforeach
                  </select>
                  <div class="form-hint">Laravel Locale for the language (for example: <code>en</code>).</div>
                </div>

                <div class="mb-3">
                  <label class="form-label" for="language-code">Language code</label>
                  <select name="code" id="language-code" class="form-select sitewyn-select2" required data-language-code data-admin-select2 data-placeholder="Select language code">
                    <option value="">Select language code</option>
                    @foreach ($languageOptions as $code => $name)
                      <option value="{{ $code }}" @selected(old('code', $language?->code) === $code)>{{ $code }}</option>
                    @endforeach
                  </select>
                  <div class="form-hint">Language code - preferably 2-letters ISO 639-1 (for example: en)</div>
                </div>

                <div class="mb-3">
                  <label class="form-label">Text direction</label>
                  <div class="form-selectgroup">
                    <label class="form-check form-check-inline">
                      <input type="radio" name="text_direction" value="ltr" class="form-check-input" @checked(old('text_direction', $language?->text_direction ?? 'ltr') === 'ltr')>
                      <span class="form-check-label">Left to right</span>
                    </label>
                    <label class="form-check form-check-inline">
                      <input type="radio" name="text_direction" value="rtl" class="form-check-input" @checked(old('text_direction', $language?->text_direction ?? 'ltr') === 'rtl')>
                      <span class="form-check-label">Right to left</span>
                    </label>
                  </div>
                  <div class="form-hint">Choose the text direction for the language</div>
                </div>

                <div class="mb-3">
                  <label class="form-label" for="language-flag">Flag</label>
                  <select name="flag" id="language-flag" class="form-select sitewyn-select2" required data-language-flag data-admin-select2 data-placeholder="Select a flag...">
                    <option value="">Select a flag...</option>
                    @foreach ($flagOptions as $code => $flag)
                      <option value="{{ $code }}" @selected(old('flag', $language?->flag) === $code)>{{ $flag['emoji'] }} {{ $flag['name'] }}</option>
                    @endforeach
                  </select>
                  <div class="form-hint">Choose a flag for the language.</div>
                </div>

                <div class="mb-3">
                  <label class="form-label" for="language-order">Order</label>
                  <input type="number" name="order" id="language-order" value="{{ old('order', $language?->order ?? 0) }}" class="form-control" min="0" max="9999" required>
                  <div class="form-hint">Position of the language in the language switcher</div>
                </div>

                <div class="btn-list">
                  <button type="submit" class="btn btn-primary">
                    {{ $language ? 'Save language' : 'Add new language' }}
                  </button>

                  @if ($language)
                    <a href="{{ route('admin.settings.languages.index', [], false) }}" class="btn">Cancel</a>
                  @endif
                </div>
              </form>
            </div>

            <div class="col-12 col-lg-7">
              <div class="alert alert-warning" role="alert">
                <div class="d-flex gap-2">
                  <div>@include('core/base::admin.partials.icon', ['name' => 'alert-circle'])</div>
                  <div>
                    <div class="fw-bold">You should set the default language only once during the initial setup and avoid changing it later.</div>
                    <p class="mb-2 mt-4">Changing the default language does not automatically update your existing content — all previously entered data remains tied to the original default language.</p>
                    <p class="mb-0">If you decide to change the default language, you will need to manually update your site content to match the new default language, as it won't be updated automatically.</p>
                  </div>
                </div>
              </div>

              <div class="table-responsive">
                <table class="table table-vcenter table-striped">
                  <thead>
                    <tr>
                      <th>Language name</th>
                      <th>Locale</th>
                      <th>Code</th>
                      <th class="text-center">Is default?</th>
                      <th class="text-center">Order</th>
                      <th class="text-center">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse ($languages as $languageRow)
                      <tr>
                        <td>
                          <span class="me-2">{{ $flagOptions[$languageRow->flag]['emoji'] ?? '🏳️' }}</span>
                          <a href="{{ route('admin.settings.languages.index', ['language' => $languageRow->id], false) }}">{{ $languageRow->name }}</a>
                        </td>
                        <td>{{ $languageRow->code }}</td>
                        <td>{{ $languageRow->locale }}</td>
                        <td class="text-center">
                          @if ($languageRow->is_default)
                            <span class="text-yellow fs-2" aria-label="Default language">★</span>
                          @else
                            <form method="POST" action="{{ route('admin.settings.languages.make-default', $languageRow, false) }}">
                              @csrf
                              <button type="submit" class="btn btn-link p-0 text-muted" aria-label="Make {{ $languageRow->name }} default">★</button>
                            </form>
                          @endif
                        </td>
                        <td class="text-center">{{ $languageRow->order }}</td>
                        <td class="text-center">
                          <div class="btn-list justify-content-center flex-nowrap">
                            <a href="{{ route('admin.settings.languages.index', ['language' => $languageRow->id], false) }}" class="btn btn-icon btn-primary" aria-label="Edit {{ $languageRow->name }}">
                              @include('core/base::admin.partials.icon', ['name' => 'pencil'])
                            </a>

                            @unless ($languageRow->is_default)
                              <button type="button" class="btn btn-icon btn-danger" data-bs-toggle="modal" data-bs-target="#language-delete-{{ $languageRow->id }}" aria-label="Delete {{ $languageRow->name }}">
                                @include('core/base::admin.partials.icon', ['name' => 'trash'])
                              </button>
                            @endunless
                          </div>
                        </td>
                      </tr>
                    @empty
                      <tr>
                        <td colspan="6" class="text-center text-secondary py-5">No languages found.</td>
                      </tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <div class="tab-pane @if ($activeTab === 'settings') active show @endif" id="languages-settings" role="tabpanel">
          <form method="POST" action="{{ route('admin.settings.languages.settings.update', [], false) }}" class="needs-validation" data-admin-validate novalidate>
            @csrf
            @method('PUT')

            <div class="mb-4">
              <label class="form-check">
                <input type="hidden" name="language_hide_default_from_url" value="0">
                <input type="checkbox" name="language_hide_default_from_url" value="1" class="form-check-input" @checked(old('language_hide_default_from_url', $settings['language_hide_default_from_url']))>
                <span class="form-check-label">Hide default language from URL?</span>
              </label>
            </div>

            <div class="mb-4">
              <label class="form-label">Language display</label>
              <div class="form-selectgroup">
                @foreach (['all' => 'Display all flag and name', 'flag' => 'Flag only', 'name' => 'Name only'] as $value => $label)
                  <label class="form-check form-check-inline">
                    <input type="radio" name="language_display" value="{{ $value }}" class="form-check-input" @checked(old('language_display', $settings['language_display']) === $value)>
                    <span class="form-check-label">{{ $label }}</span>
                  </label>
                @endforeach
              </div>
            </div>

            <div class="mb-4">
              <label class="form-label">Switcher language display</label>
              <div class="form-selectgroup">
                @foreach (['dropdown' => 'Dropdown', 'list' => 'List'] as $value => $label)
                  <label class="form-check form-check-inline">
                    <input type="radio" name="language_switcher_display" value="{{ $value }}" class="form-check-input" @checked(old('language_switcher_display', $settings['language_switcher_display']) === $value)>
                    <span class="form-check-label">{{ $label }}</span>
                  </label>
                @endforeach
              </div>
            </div>

            <div class="mb-4">
              <label class="form-label">Hide languages</label>
              <div class="border rounded p-3">
                @foreach ($languages->where('is_default', false) as $languageRow)
                  <label class="form-check mb-3">
                    <input type="checkbox" name="language_hidden_codes[]" value="{{ $languageRow->code }}" class="form-check-input" @checked(in_array($languageRow->code, old('language_hidden_codes', $settings['language_hidden_codes']), true))>
                    <span class="form-check-label">{{ $languageRow->name }}</span>
                  </label>
                @endforeach
              </div>
            </div>

            @if (count(old('language_hidden_codes', $settings['language_hidden_codes'])) === 0)
              <div class="alert alert-info" role="alert">
                <div class="d-flex gap-2">
                  <div>@include('core/base::admin.partials.icon', ['name' => 'info-circle'])</div>
                  <div>All languages are currently displayed.</div>
                </div>
              </div>
            @endif

            <div class="mb-4">
              <label class="form-check">
                <input type="hidden" name="language_auto_detect" value="0">
                <input type="checkbox" name="language_auto_detect" value="1" class="form-check-input" @checked(old('language_auto_detect', $settings['language_auto_detect']))>
                <span class="form-check-label">Auto detect user language?</span>
              </label>
              <div class="form-hint ms-4">If enabled, the system will try to detect the user language based on the browser language.</div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg">
              @include('core/base::admin.partials.icon', ['name' => 'save'])
              <span class="ms-2">Save settings</span>
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>

  @foreach ($languages as $languageRow)
    @unless ($languageRow->is_default)
      <x-admin-modal id="language-delete-{{ $languageRow->id }}" title="Delete language">
        <p>Delete <strong>{{ $languageRow->name }} ({{ $languageRow->code }})</strong>?</p>
        <p class="text-secondary mb-0">All page, post, and category translations of this language are removed with it.</p>
        <form method="POST" action="{{ route('admin.settings.languages.destroy', $languageRow, false) }}" id="language-delete-form-{{ $languageRow->id }}">
          @csrf
        </form>

        <x-slot:footer>
          <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger" form="language-delete-form-{{ $languageRow->id }}">Delete language</button>
        </x-slot:footer>
      </x-admin-modal>
    @endunless
  @endforeach
@endsection

@push('scripts')
  <script src="{{ asset('vendor/core-base/libraries/jquery.min.js') }}"></script>
  <script src="{{ asset('vendor/core-base/libraries/select2/js/select2.full.min.js') }}"></script>
  <script>
    ;(function () {
      var preset = document.querySelector('[data-language-preset]')
      var nameInput = document.querySelector('[data-language-name]')
      var localeSelect = document.querySelector('[data-language-locale]')
      var codeSelect = document.querySelector('[data-language-code]')
      var flagSelect = document.querySelector('[data-language-flag]')
      var directionInputs = document.querySelectorAll('input[name="text_direction"]')
      var select2Fields = document.querySelectorAll('[data-admin-select2]')

      if (!preset) {
        return
      }

      var syncSelect2Value = function (field, value) {
        if (!field) {
          return
        }

        field.value = value || ''

        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
          window.jQuery(field).trigger('change.select2')
        }
      }

      if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
        select2Fields.forEach(function (field) {
          window.jQuery(field).select2({
            placeholder: field.getAttribute('data-placeholder') || 'Select an option',
            width: '100%',
            dropdownCssClass: 'sitewyn-language-select2-dropdown',
          })
        })
      }

      preset.addEventListener('change', function () {
        var option = preset.options[preset.selectedIndex]

        if (!option || !option.value) {
          return
        }

        if (nameInput) nameInput.value = option.getAttribute('data-name') || ''
        syncSelect2Value(localeSelect, option.getAttribute('data-locale') || '')
        syncSelect2Value(codeSelect, option.value)
        syncSelect2Value(flagSelect, option.getAttribute('data-flag') || '')

        directionInputs.forEach(function (input) {
          input.checked = input.value === (option.getAttribute('data-direction') || 'ltr')
        })
      })
    })()
  </script>
@endpush
