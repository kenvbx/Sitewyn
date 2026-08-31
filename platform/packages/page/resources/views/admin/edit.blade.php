@extends('core/base::admin.layouts.master')

@section('title', 'Edit page - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('pretitle', 'Content')

@section('page-title', 'Edit page')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.pages.index') }}">Pages</a></li>
  <li class="breadcrumb-item active" aria-current="page">{{ $page->title }}</li>
@endsection

@section('page-actions')
  <div class="btn-list">
    @if ($page->status === 'published')
      <a href="{{ url('/'.$page->slug) }}" target="_blank" rel="noopener" class="btn" aria-label="View page on the site">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
          <path d="M2 8a4 4 0 0 1 4 -4h2" />
          <path d="M2 12a6 6 0 0 1 6 -6h.5" />
          <path d="M14.09 17.072l-.637 -.378" />
          <path d="M10.648 16.117l-.636 -.377" />
          <path d="M17.533 16.35l-.636 -.378" />
          <path d="M10 11l0 2" />
          <path d="M14 11l0 2" />
          <path d="M22 12c-1.946 3.68 -5.034 6 -10 6c-4.966 0 -8.054 -2.32 -10 -6c1.946 -3.68 5.034 -6 10 -6c4.966 0 8.054 2.32 10 6z" />
        </svg>
        View page
      </a>
    @endif
    @can('page.index')
      <a href="{{ route('admin.pages.preview', $page) }}" target="_blank" rel="noopener" class="btn">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
          <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
          <path d="M2 12c1.946 -3.68 5.034 -6 10 -6c4.966 0 8.054 2.32 10 6c-1.946 3.68 -5.034 6 -10 6c-4.966 0 -8.054 -2.32 -10 -6z" />
        </svg>
        Preview
      </a>
    @endcan
    <a href="{{ route('admin.pages.index') }}" class="btn">Back</a>
  </div>
@endsection

@section('content')
  <form method="POST" action="{{ route('admin.pages.update', $page) }}" class="needs-validation" data-admin-validate novalidate>
    @csrf
    @method('PUT')
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
