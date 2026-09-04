@extends('core/base::admin.layouts.master')

@section('title', 'Locales - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.settings.edit') }}">Settings</a></li>
  <li class="breadcrumb-item">Localization</li>
  <li class="breadcrumb-item active" aria-current="page">Locales</li>
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
  <div class="row g-4">
    <div class="col-12 col-xl-5">
      <div class="card">
        <form method="POST" action="{{ route('admin.translations.locales.store', [], false) }}" class="card-body">
          @csrf

          <div class="mb-3">
            <label class="form-label" for="locale">Locale</label>
            <select name="locale" id="locale" class="form-select sitewyn-select2 @error('locale') is-invalid @enderror" data-admin-select2 data-placeholder="Select locale" required>
              <option value="">Select locale</option>
              @foreach ($localeOptions as $value => $label)
                <option value="{{ $value }}" @selected(old('locale') === $value)>{{ $label }}</option>
              @endforeach
            </select>
            @error('locale')
              <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
          </div>

          <button type="submit" class="btn btn-primary">Add new locale</button>
        </form>
      </div>
    </div>

    <div class="col-12 col-xl-7">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Locales</h3>
        </div>

        <div class="table-responsive">
          <table class="table table-vcenter card-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Locale</th>
                <th>Is default?</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($languages as $language)
                <tr>
                  <td>{{ $language->name }}</td>
                  <td>{{ $language->code }}</td>
                  <td>{{ $language->is_default ? 'Yes' : 'No' }}</td>
                  <td class="text-end">
                    <div class="btn-list justify-content-end flex-nowrap">
                      <a href="{{ route('admin.translations.locales.download', $language, false) }}" class="btn btn-icon btn-primary" aria-label="Download {{ $language->name }}">
                        @include('core/base::admin.partials.icon', ['name' => 'download'])
                      </a>

                      @unless ($language->is_default)
                        <button type="button" class="btn btn-icon btn-danger" data-bs-toggle="modal" data-bs-target="#locale-delete-{{ $language->id }}" aria-label="Delete {{ $language->name }}">
                          @include('core/base::admin.partials.icon', ['name' => 'trash'])
                        </button>
                      @endunless
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="4" class="text-center text-secondary py-5">No locales found.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  @foreach ($languages as $language)
    @unless ($language->is_default)
      <x-admin-modal id="locale-delete-{{ $language->id }}" title="Delete locale">
        <p>Delete <strong>{{ $language->name }} ({{ $language->code }})</strong>?</p>
        <p class="text-secondary mb-0">All translations of this locale are removed with it.</p>
        <form method="POST" action="{{ route('admin.translations.locales.destroy', $language, false) }}" id="locale-delete-form-{{ $language->id }}">
          @csrf
        </form>

        <x-slot:footer>
          <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger" form="locale-delete-form-{{ $language->id }}">Delete</button>
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
      var select2Fields = document.querySelectorAll('[data-admin-select2]')

      if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
        select2Fields.forEach(function (field) {
          window.jQuery(field).select2({
            placeholder: field.getAttribute('data-placeholder') || 'Select an option',
            width: '100%',
            dropdownCssClass: 'sitewyn-language-select2-dropdown',
          })
        })
      }
    })()
  </script>
@endpush
