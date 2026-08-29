@extends('core/base::admin.layouts.master')

@section('title', 'Create user - ' . config('app.name', 'Sitewyn') . ' Admin')
@section('pretitle', 'Access Control')
@section('page-title', 'Create user')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li>
  <li class="breadcrumb-item active" aria-current="page">Create</li>
@endsection

@section('page-actions')
  <a href="{{ route('admin.users.index') }}" class="btn">Back</a>
@endsection

@section('content')
  <form method="POST" action="{{ route('admin.users.store') }}" class="needs-validation" data-admin-validate novalidate>
    @csrf
    @include('core/base::admin.users.form')
  </form>
@endsection
