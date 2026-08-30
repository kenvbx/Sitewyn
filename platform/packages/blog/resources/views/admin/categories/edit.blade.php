@extends('core/base::admin.layouts.master')

@section('title', 'Edit category - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('pretitle', 'Content')

@section('page-title', 'Edit category')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.categories.index') }}">Categories</a></li>
  <li class="breadcrumb-item active" aria-current="page">{{ $category->name }}</li>
@endsection

@section('page-actions')
  <a href="{{ route('admin.categories.index') }}" class="btn">Back</a>
@endsection

@section('content')
  <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="needs-validation" data-admin-validate novalidate>
    @csrf
    @method('PUT')
    @include('package/blog::admin.categories.form')
  </form>
@endsection
