@extends('frontend.layout')

@php
    // Localized rendering (P5-01): the translation only overrides the fields
    // it fills; every missing field falls back to the default-language post.
    $title = $translation?->title ?: $post->title;
    $content = $translation?->content ?: $post->content;
    $seoTitle = $translation?->seo_title ?: $post->seo_title;
    $seoDescription = $translation?->seo_description ?: $post->seo_description;
@endphp

@section('title', $seoTitle ?: $title)

@push('meta')
  @if (filled($seoDescription))
    <meta name="description" content="{{ $seoDescription }}" />
    <meta property="og:description" content="{{ $seoDescription }}" />
  @endif
  <meta property="og:type" content="article" />
  <meta property="og:title" content="{{ $seoTitle ?: $title }}" />
  @if (filled($post->og_image))
    <meta property="og:image" content="{{ $post->og_image }}" />
  @endif
  @if (site_blog_schema_enabled())
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => site_blog_schema_type(),
        'headline' => $title,
        'description' => $seoDescription,
        'datePublished' => $post->created_at?->toAtomString(),
        'dateModified' => $post->updated_at?->toAtomString(),
        'image' => filled($post->og_image) ? $post->og_image : $post->featured_image,
        'mainEntityOfPage' => url('/blog/'.$post->slug),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
  @endif
@endpush

@section('content')
  <article class="entry">
    <h1>{{ $title }}</h1>
    @if (filled($post->featured_image))
      <img src="{{ $post->featured_image }}" class="post-featured" alt="{{ $title }}" />
    @endif
    <p class="post-meta">
      <time datetime="{{ $post->updated_at->toDateString() }}">{{ $post->updated_at->format('F j, Y') }}</time>
      @if ($post->tags->isNotEmpty())
        {{-- No public tag archive exists yet (P5-02), so tags stay plain
             accent-colored text instead of dead links. --}}
        <span class="post-tags">
          @foreach ($post->tags as $tag)
            <span class="post-tag">{{ $tag->name }}</span>@if (! $loop->last), @endif
          @endforeach
        </span>
      @endif
    </p>
    <div class="entry-content">
      {{-- Rich text is authored by admins holding post.create/post.edit and is rendered as stored HTML. --}}
      {!! site_blog_content($content) !!}
    </div>
  </article>
@endsection
