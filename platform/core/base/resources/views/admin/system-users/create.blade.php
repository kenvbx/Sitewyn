@extends('core/base::admin.layouts.master')

@section('title', 'Create team user - ' . config('app.name', 'Sitewyn') . ' Admin')
@section('pretitle', 'System')
@section('page-title', 'Create team user')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.system') }}">Platform Administration</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.system.users.index') }}">Team users</a></li>
  <li class="breadcrumb-item active" aria-current="page">Create</li>
@endsection

@section('page-actions')
  <a href="{{ route('admin.system.users.index') }}" class="btn">Back</a>
@endsection

@section('content')
  <form method="POST" action="{{ route('admin.system.users.store') }}" class="needs-validation" data-admin-validate novalidate>
    @csrf
    @include('core/base::admin.system-users.form')
  </form>
@endsection
