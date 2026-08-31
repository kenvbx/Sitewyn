@extends('frontend.layout')

@php
    // The child view renders before the layout body's @php block runs, so
    // the site name is resolved here again for the <title> and empty state.
    $siteName = site_setting('site_name', config('app.name', 'Sitewyn'));
@endphp

@section('title', $siteName)

@section('content')
  <article class="entry">
    @if ($posts->isEmpty() && $pages->isEmpty())
      <h1>{{ $siteName }}</h1>
      {{-- MVP copy is English-only; themable strings are a later phase. --}}
      <p>No content yet. Sign in to the admin to create your first page or post.</p>
    @else
      @if ($posts->isNotEmpty())
        <section class="home-posts">
          <h1>Latest posts</h1>
          <ul class="post-list">
            @foreach ($posts as $post)
              <li class="post-list-item">
                <h2 class="post-list-title"><a href="/blog/{{ $post->slug }}">{{ $post->title }}</a></h2>
                <p class="post-meta">
                  <time datetime="{{ $post->updated_at->toDateString() }}">{{ $post->updated_at->format('F j, Y') }}</time>
                </p>
                @if (filled($post->excerpt))
                  <p class="post-excerpt">{{ $post->excerpt }}</p>
                @endif
              </li>
            @endforeach
          </ul>
        </section>
      @endif
      @if ($pages->isNotEmpty())
        <section class="home-pages">
          <h2>Pages</h2>
          <ul class="page-list">
            @foreach ($pages as $page)
              <li><a href="/{{ $page->slug }}">{{ $page->title }}</a></li>
            @endforeach
          </ul>
        </section>
      @endif
    @endif
  </article>
@endsection
