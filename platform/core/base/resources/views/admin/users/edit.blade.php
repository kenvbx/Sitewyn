@extends('core/base::admin.layouts.master')

@section('title', 'Edit user - ' . config('app.name', 'Sitewyn') . ' Admin')
@section('pretitle', 'Access Control')
@section('page-title', 'Edit user')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li>
  <li class="breadcrumb-item active" aria-current="page">{{ $user->name }}</li>
@endsection

@section('page-actions')
  <a href="{{ route('admin.users.index') }}" class="btn">Back</a>
@endsection

@section('content')
  <form method="POST" action="{{ route('admin.users.update', $user) }}" class="needs-validation" data-admin-validate novalidate>
    @csrf
    @method('PUT')
    @include('core/base::admin.users.form')
  </form>
@endsection
