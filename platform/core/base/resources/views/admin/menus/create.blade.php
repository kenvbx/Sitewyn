@extends('core/base::admin.layouts.master')

@section('title', 'New menu - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('pretitle', 'Content')

@section('page-title', 'New menu')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.menus.index') }}">Menus</a></li>
  <li class="breadcrumb-item active" aria-current="page">New menu</li>
@endsection

@section('content')
  <div class="row row-cards">
    <div class="col-lg-7">
      <x-admin-card
        title="Menu details"
        subtitle="After creating the menu you land in the builder to add and arrange its items."
      >
        @include('core/base::admin.menus.form')
      </x-admin-card>
    </div>
  </div>
@endsection
