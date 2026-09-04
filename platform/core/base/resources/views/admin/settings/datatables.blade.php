@extends('core/base::admin.layouts.master')

@section('title', 'Datatables - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.settings.edit') }}">Settings</a></li>
  <li class="breadcrumb-item active" aria-current="page">Datatables</li>
@endsection

@section('content')
  @if (session('status'))
    <div class="alert alert-success" role="alert">{{ session('status') }}</div>
  @endif

  <form id="datatables-settings-form" method="POST" action="{{ route('admin.settings.datatables.update', [], false) }}" class="needs-validation" data-admin-validate novalidate>
    @csrf
    @method('PUT')
    <input type="hidden" name="site_name" value="{{ $baseSettings['site_name'] }}">
    <input type="hidden" name="site_logo" value="{{ $baseSettings['site_logo'] }}">
    <input type="hidden" name="robots_txt" value="{{ $baseSettings['robots_txt'] }}">
    <input type="hidden" name="active_theme" value="{{ $baseSettings['active_theme'] }}">

    <div class="row mb-5 d-block d-md-flex">
      <div class="col-12 col-md-3">
        <h2>Datatables</h2>
        <p class="text-muted">Settings for datatables</p>
      </div>

      <div class="col-12 col-md-9">
        <div class="card">
          <div class="card-body">
            <div class="mb-4">
              <label class="form-label" for="datatables-pagination-type">Pagination type</label>
              <select name="datatables_pagination_type" id="datatables-pagination-type" class="form-select">
                @foreach ($paginationTypeOptions as $value => $label)
                  <option value="{{ $value }}" @selected(old('datatables_pagination_type', $settings['datatables_pagination_type']) === $value)>{{ $label }}</option>
                @endforeach
              </select>
              <div class="form-hint">Choose how pagination controls are displayed: Default shows page numbers, Dropdown shows a compact dropdown selector.</div>
            </div>

            <div class="mb-4">
              <label class="form-check">
                <input type="hidden" name="datatables_show_column_visibility" value="0">
                <input type="checkbox" name="datatables_show_column_visibility" value="1" class="form-check-input" @checked(old('datatables_show_column_visibility', $settings['datatables_show_column_visibility']))>
                <span class="form-check-label">Show column visibility by default</span>
              </label>
              <div class="form-hint ms-4">Enable the column visibility toggle button in data tables to allow users to show/hide columns.</div>
            </div>

            <div class="mb-4">
              <label class="form-check">
                <input type="hidden" name="datatables_show_export_button" value="0">
                <input type="checkbox" name="datatables_show_export_button" value="1" class="form-check-input" @checked(old('datatables_show_export_button', $settings['datatables_show_export_button']))>
                <span class="form-check-label">Show export button by default</span>
              </label>
              <div class="form-hint ms-4">Display export options (CSV, Excel, PDF) in data tables for downloading table data.</div>
            </div>

            <div class="mb-0">
              <label class="form-check">
                <input type="hidden" name="datatables_enable_table_responsive" value="0">
                <input type="checkbox" name="datatables_enable_table_responsive" value="1" class="form-check-input" @checked(old('datatables_enable_table_responsive', $settings['datatables_enable_table_responsive']))>
                <span class="form-check-label">Enable table responsive</span>
              </label>
              <div class="form-hint ms-4">Automatically adjust table columns to fit different screen sizes for better mobile experience.</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </form>

  <div class="row mb-5 d-block d-md-flex">
    <div class="col-12 col-md-3"></div>
    <div class="col-12 col-md-9">
      <button type="submit" class="btn btn-primary btn-lg" form="datatables-settings-form">
        @include('core/base::admin.partials.icon', ['name' => 'save'])
        <span class="ms-2">Save settings</span>
      </button>
    </div>
  </div>
@endsection
