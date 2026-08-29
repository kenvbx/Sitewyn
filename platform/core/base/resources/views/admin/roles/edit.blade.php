@extends('core/base::admin.layouts.master')

@section('title', 'Edit role - ' . config('app.name', 'Sitewyn') . ' Admin')
@section('pretitle', 'Access Control')
@section('page-title', 'Edit role')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.roles.index') }}">Roles</a></li>
  <li class="breadcrumb-item active" aria-current="page">{{ $role->name }}</li>
@endsection

@section('page-actions')
  <a href="{{ route('admin.roles.index') }}" class="btn">Back</a>
@endsection

@section('content')
  <form method="POST" action="{{ route('admin.roles.update', $role) }}" class="needs-validation" data-admin-validate novalidate>
    @csrf
    @method('PUT')
    @include('core/base::admin.roles.form')
  </form>
@endsection
