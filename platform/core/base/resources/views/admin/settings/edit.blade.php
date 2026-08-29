@extends('core/base::admin.layouts.master')

@section('title', 'Settings - ' . config('app.name', 'Sitewyn') . ' Admin')
@section('pretitle', 'System')
@section('page-title', 'Settings')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item active" aria-current="page">Settings</li>
@endsection

@section('page-actions')
  <a href="{{ route('admin.dashboard') }}" class="btn">Back</a>
@endsection

@section('content')
  <form method="POST" action="{{ route('admin.settings.update') }}" class="needs-validation" data-admin-validate novalidate>
    @csrf
    @method('PUT')

    <div class="row row-cards">
      <div class="col-lg-7">
        <x-admin-card title="General settings">
          <x-admin-form-group
            name="site_name"
            label="Site name"
            :value="$settings['site_name']"
            required
            autocomplete="off"
            :maxlength="255"
            invalid-feedback="Site name is required."
          />

          <x-admin-form-group
            name="site_logo"
            label="Site logo"
            :value="$settings['site_logo']"
            autocomplete="off"
            :maxlength="2048"
            placeholder="/storage/logo.svg"
            invalid-feedback="Site logo may not be greater than 2048 characters."
          />

          <x-slot:footer>
            <div class="text-end">
              <button type="submit" class="btn btn-primary">Save settings</button>
            </div>
          </x-slot:footer>
        </x-admin-card>
      </div>

      <div class="col-lg-5">
        <x-admin-card title="Brand preview">
          <div class="d-flex align-items-center gap-3">
            @if ($settings['site_logo'])
              <span class="avatar avatar-xl bg-transparent">
                <img src="{{ $settings['site_logo'] }}" alt="{{ $settings['site_name'] }}" />
              </span>
            @else
              <span class="avatar avatar-xl">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($settings['site_name'], 0, 2)) }}</span>
            @endif
            <div>
              <div class="h3 mb-1">{{ $settings['site_name'] }}</div>
              <div class="text-secondary">{{ config('app.url') }}</div>
            </div>
          </div>
        </x-admin-card>
      </div>
    </div>
  </form>
@endsection
