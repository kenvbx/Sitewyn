@extends('core/base::admin.layouts.master')

@section('title', 'Languages - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('pretitle', 'System')

@section('page-title', 'Languages')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.settings.edit') }}">Settings</a></li>
  <li class="breadcrumb-item active" aria-current="page">Languages</li>
@endsection

@section('content')
  <div class="row row-cards">
    <div class="col-lg-7">
      <x-admin-card
        title="Site languages"
        subtitle="The site ships with English as its single default language. Every language added here unlocks translation fields on pages, posts, and categories."
      >
        <div class="table-responsive">
          <table class="table table-vcenter" id="admin-languages-table">
            <thead>
              <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Status</th>
                <th class="w-1"></th>
              </tr>
            </thead>
            <tbody>
              @forelse ($languages as $language)
                <tr>
                  <td><code>{{ $language->code }}</code></td>
                  <td class="fw-medium">{{ $language->name }}</td>
                  <td>
                    @if ($language->is_default)
                      <span class="badge bg-green-lt">Default</span>
                    @elseif ($language->is_active)
                      <span class="badge bg-secondary-lt">Active</span>
                    @else
                      <span class="badge bg-secondary-lt">Inactive</span>
                    @endif
                  </td>
                  <td>
                    @unless ($language->is_default)
                      <div class="btn-list flex-nowrap justify-content-end">
                        <form method="POST" action="{{ route('admin.settings.languages.make-default', $language, false) }}">
                          @csrf
                          <button type="submit" class="btn btn-sm">Make default</button>
                        </form>
                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#language-delete-{{ $language->id }}" aria-label="Delete language {{ $language->code }}">
                          Delete
                        </button>
                      </div>
                    @endunless
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="4" class="text-center text-secondary py-5">No languages found.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <x-slot:footer>
          <div class="form-hint">
            Deleting a language also deletes its page, post, and category translations. The default language can never be deleted or inactive.
          </div>
        </x-slot:footer>
      </x-admin-card>
    </div>

    <div class="col-lg-5">
      <x-admin-card title="Add language" subtitle="Codes are two lowercase letters (ISO 639-1), e.g. vi, fr, de.">
        <form method="POST" action="{{ route('admin.settings.languages.store', [], false) }}" class="needs-validation" data-admin-validate novalidate>
          @csrf
          <x-admin-form-group
            name="code"
            label="Code"
            :value="old('code')"
            required
            autocomplete="off"
            :maxlength="2"
            pattern="[a-z]{2}"
            placeholder="vi"
            invalid-feedback="Use a two-letter lowercase code that is not taken yet."
          />
          <x-admin-form-group
            name="name"
            label="Name"
            :value="old('name')"
            required
            autocomplete="off"
            :maxlength="255"
            placeholder="Vietnamese"
            invalid-feedback="Name is required."
          />

          <div class="text-end">
            <button type="submit" class="btn btn-primary">Add language</button>
          </div>
        </form>
      </x-admin-card>
    </div>
  </div>

  @foreach ($languages as $language)
    @unless ($language->is_default)
      <x-admin-modal id="language-delete-{{ $language->id }}" title="Delete language">
        <p>Delete <strong>{{ $language->name }} ({{ $language->code }})</strong>?</p>
        <p class="text-secondary mb-0">All page, post, and category translations of this language are removed with it.</p>
        <form method="POST" action="{{ route('admin.settings.languages.destroy', $language, false) }}" id="language-delete-form-{{ $language->id }}">
          @csrf
        </form>

        <x-slot:footer>
          <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger" form="language-delete-form-{{ $language->id }}">Delete language</button>
        </x-slot:footer>
      </x-admin-modal>
    @endunless
  @endforeach
@endsection
