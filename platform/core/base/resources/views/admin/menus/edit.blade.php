@extends('core/base::admin.layouts.master')

@section('title', 'Edit menu - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('pretitle', 'Content')

@section('page-title', 'Edit menu')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.menus.index') }}">Menus</a></li>
  <li class="breadcrumb-item active" aria-current="page">{{ $menu->name }}</li>
@endsection

@section('page-actions')
  <div class="btn-list">
    <a href="{{ route('admin.menus.edit-items', $menu) }}" class="btn btn-primary">
      Edit items
    </a>
  </div>
@endsection

@section('content')
  <div class="row row-cards">
    <div class="col-lg-7">
      <x-admin-card
        title="Menu details"
        subtitle="Name, slug, and theme location of this menu. Use Edit items to arrange the links themselves."
      >
        @include('core/base::admin.menus.form')
      </x-admin-card>
    </div>
  </div>
@endsection
