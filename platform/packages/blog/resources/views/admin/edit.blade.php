@extends('core/base::admin.layouts.master')

@section('title', 'Edit post - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('pretitle', 'Content')

@section('page-title', 'Edit post')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.posts.index') }}">Posts</a></li>
  <li class="breadcrumb-item active" aria-current="page">{{ $post->title }}</li>
@endsection

@section('page-actions')
  <div class="btn-list">
    @can('post.index')
      <a href="{{ route('admin.posts.preview', $post) }}" target="_blank" rel="noopener" class="btn">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
          <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
          <path d="M2 12c1.946 -3.68 5.034 -6 10 -6c4.966 0 8.054 2.32 10 6c-1.946 3.68 -5.034 6 -10 6c-4.966 0 -8.054 -2.32 -10 -6z" />
        </svg>
        Preview
      </a>
    @endcan
    <a href="{{ route('admin.posts.index') }}" class="btn">Back</a>
  </div>
@endsection

@section('content')
  <form method="POST" action="{{ route('admin.posts.update', $post) }}" class="needs-validation" data-admin-validate novalidate>
    @csrf
    @method('PUT')
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
