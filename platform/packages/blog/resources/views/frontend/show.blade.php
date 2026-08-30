<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $post->seo_title ?: $post->title }}</title>
    @if (filled($post->seo_description))
      <meta name="description" content="{{ $post->seo_description }}" />
      <meta property="og:description" content="{{ $post->seo_description }}" />
    @endif
    <meta property="og:type" content="article" />
    <meta property="og:title" content="{{ $post->seo_title ?: $post->title }}" />
    @if (filled($post->og_image))
      <meta property="og:image" content="{{ $post->og_image }}" />
    @endif
    <style>
      body {
        margin: 0;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        color: #182433;
        background: #fff;
      }

      .site-header {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #e3e6ec;
        font-weight: 600;
      }

      .site-header a {
        color: inherit;
        text-decoration: none;
      }

      .page-content {
        max-width: 48rem;
        margin: 0 auto;
        padding: 3rem 1.5rem 4rem;
      }

      .page-content img {
        max-width: 100%;
        height: auto;
      }

      .post-meta {
        margin: 0 0 2rem;
        font-size: 0.875rem;
        color: #626976;
      }

      .post-featured {
        display: block;
        max-width: 100%;
        height: auto;
        margin: 0 0 2rem;
        border-radius: 4px;
      }

      .post-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin: 0 0 2rem;
        padding: 0;
        list-style: none;
      }

      .post-tags li {
        padding: 0.15rem 0.6rem;
        font-size: 0.8125rem;
        color: #3f5470;
        background: #eef2f7;
        border-radius: 999px;
      }
    </style>
  </head>
  <body>
    <header class="site-header">
      <a href="/">{{ config('app.name', 'Sitewyn') }}</a>
    </header>
    <main class="page-content">
      <h1>{{ $post->title }}</h1>
      <p class="post-meta">
        <time datetime="{{ $post->updated_at->toDateString() }}">{{ $post->updated_at->format('F j, Y') }}</time>
      </p>
      @if ($post->tags->isNotEmpty())
        <ul class="post-tags">
          @foreach ($post->tags as $tag)
            <li>{{ $tag->name }}</li>
          @endforeach
        </ul>
      @endif
      @if (filled($post->featured_image))
        <img src="{{ $post->featured_image }}" class="post-featured" alt="{{ $post->title }}" />
      @endif
      {{-- Rich text is authored by admins holding post.create/post.edit and is rendered as stored HTML. --}}
      {!! $post->content !!}
    </main>
  </body>
</html>
