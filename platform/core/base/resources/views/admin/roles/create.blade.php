@extends('core/base::admin.layouts.master')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.system.roles.index') }}">Roles</a></li>
  <li class="breadcrumb-item active" aria-current="page">Create</li>
@endsection

@section('content')
  <form method="POST" action="{{ route('admin.system.roles.store') }}" class="needs-validation" data-admin-validate novalidate>
    @csrf
    @include('core/base::admin.roles.form')
  </form>
@endsection
