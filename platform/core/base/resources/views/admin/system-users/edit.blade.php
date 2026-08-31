@extends('core/base::admin.layouts.master')

@section('title', 'Edit team user - ' . config('app.name', 'Sitewyn') . ' Admin')
@section('pretitle', 'System')
@section('page-title', 'Edit team user')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.system') }}">Platform Administration</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.system.users.index') }}">Team users</a></li>
  <li class="breadcrumb-item active" aria-current="page">{{ $user->name }}</li>
@endsection

@section('page-actions')
  <a href="{{ route('admin.system.users.index') }}" class="btn">Back</a>
@endsection

@section('content')
  <form method="POST" action="{{ route('admin.system.users.update', $user) }}" class="needs-validation" data-admin-validate novalidate>
    @csrf
    @method('PUT')
    @include('core/base::admin.system-users.form')
  </form>
@endsection
