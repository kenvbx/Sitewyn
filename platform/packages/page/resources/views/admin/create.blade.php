@extends('core/base::admin.layouts.master')

@section('title', 'Create page - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('pretitle', 'Content')

@section('page-title', 'Create page')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.pages.index') }}">Pages</a></li>
  <li class="breadcrumb-item active" aria-current="page">Create</li>
@endsection

@section('page-actions')
  <a href="{{ route('admin.pages.index') }}" class="btn">Back</a>
@endsection

@section('content')
  <form method="POST" action="{{ route('admin.pages.store') }}" class="needs-validation" data-admin-validate novalidate>
    @csrf
    @include('package/page::admin.form')
  </form>

  @can('media.index')
    {{-- The media picker lives outside the page form because it ships its own
         search <form>; nesting it would break both. It only powers the editor
         bridge, so its hidden inputs never need to submit with the page. --}}
    <div class="row row-cards">
      <div class="col-lg-4 offset-lg-8">
        <x-admin-card title="Media">
          <x-media-picker
            name="page_media"
            hint="This picker powers the editor's Image button — open it from the editor toolbar to insert images from the media library."
          />
        </x-admin-card>
      </div>
    </div>
  @endcan
@endsection
