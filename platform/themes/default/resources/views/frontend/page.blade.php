@extends('frontend.layout')

@php
    // Localized rendering (P5-01): the translation only overrides the fields
    // it fills; every missing field falls back to the default-language page.
    $title = $translation?->title ?: $page->title;
    $content = $translation?->content ?: $page->content;
    $seoTitle = $translation?->seo_title ?: $page->seo_title;
    $seoDescription = $translation?->seo_description ?: $page->seo_description;
@endphp

@section('title', $seoTitle ?: $title)

@push('meta')
  @if (filled($seoDescription))
    <meta name="description" content="{{ $seoDescription }}" />
    <meta property="og:description" content="{{ $seoDescription }}" />
  @endif
  <meta property="og:type" content="website" />
  <meta property="og:title" content="{{ $seoTitle ?: $title }}" />
  @if (filled($page->og_image))
    <meta property="og:image" content="{{ $page->og_image }}" />
  @endif
@endpush

@section('content')
  <article class="entry">
    <h1>{{ $title }}</h1>
    <div class="entry-content">
      {{-- Rich text is authored by admins holding page.create/page.edit and is rendered as stored HTML. --}}
      {!! $content !!}
    </div>
  </article>
@endsection
