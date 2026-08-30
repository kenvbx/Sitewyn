@extends('core/base::admin.layouts.master')

@section('title', 'Create post - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('pretitle', 'Content')

@section('page-title', 'Create post')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.posts.index') }}">Posts</a></li>
  <li class="breadcrumb-item active" aria-current="page">Create</li>
@endsection

@section('page-actions')
  <a href="{{ route('admin.posts.index') }}" class="btn">Back</a>
@endsection

@section('content')
  <form method="POST" action="{{ route('admin.posts.store') }}" class="needs-validation" data-admin-validate novalidate>
    @csrf
    @include('package/blog::admin.form')
  </form>

  @can('media.index')
    <div class="row row-cards">
      {{-- The media picker lives outside the post form because it ships its
           own search <form>; nesting it would break both. It powers the
           editor's Image button and the featured image chooser, so its hidden
           inputs never need to submit with the post. --}}
      <div class="col-lg-4 offset-lg-8">
        <x-admin-card title="Media">
          <x-media-picker
            name="post_media"
            hint="This picker powers the editor's Image button and the featured image chooser — open it from either to select files from the media library."
          />
        </x-admin-card>
      </div>
    </div>
  @endcan
@endsection
