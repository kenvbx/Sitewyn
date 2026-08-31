@extends('core/base::admin.layouts.master')

@section('title', 'New widget - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('pretitle', 'Content')

@section('page-title', 'New widget')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.widgets.index') }}">Widgets</a></li>
  <li class="breadcrumb-item active" aria-current="page">New widget</li>
@endsection

@section('content')
  <div class="row row-cards">
    <div class="col-lg-7">
      <x-admin-card
        title="Widget details"
        subtitle="The new widget joins the bottom of the area; use the arrows on the area page to rearrange it."
      >
        @include('core/base::admin.widgets.form')
      </x-admin-card>
    </div>
  </div>
@endsection
