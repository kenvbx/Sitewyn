@extends('core/base::admin.layouts.master')

@section('title', 'Edit tag - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('pretitle', 'Content')

@section('page-title', 'Edit tag')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.tags.index') }}">Tags</a></li>
  <li class="breadcrumb-item active" aria-current="page">{{ $tag->name }}</li>
@endsection

@section('page-actions')
  <a href="{{ route('admin.tags.index') }}" class="btn">Back</a>
@endsection

@section('content')
  <form method="POST" action="{{ route('admin.tags.update', $tag) }}" class="needs-validation" data-admin-validate novalidate>
    @csrf
    @method('PUT')
    @include('package/blog::admin.tags.form')
  </form>
@endsection
