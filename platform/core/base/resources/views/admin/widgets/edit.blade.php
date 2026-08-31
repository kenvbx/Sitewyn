@extends('core/base::admin.layouts.master')

@section('title', 'Edit widget - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('pretitle', 'Content')

@section('page-title', 'Edit widget')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.widgets.index') }}">Widgets</a></li>
  <li class="breadcrumb-item active" aria-current="page">Edit widget</li>
@endsection

@section('content')
  <div class="row row-cards">
    <div class="col-lg-7">
      <x-admin-card
        title="Widget details"
        subtitle="A widget always stays in the area it was created for — move it up or down from the area page instead."
      >
        @include('core/base::admin.widgets.form')
      </x-admin-card>
    </div>
  </div>
@endsection
