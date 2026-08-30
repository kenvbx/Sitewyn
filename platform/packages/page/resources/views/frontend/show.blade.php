<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $page->seo_title ?: $page->title }}</title>
    @if (filled($page->seo_description))
      <meta name="description" content="{{ $page->seo_description }}" />
      <meta property="og:description" content="{{ $page->seo_description }}" />
    @endif
    <meta property="og:type" content="website" />
    <meta property="og:title" content="{{ $page->seo_title ?: $page->title }}" />
    @if (filled($page->og_image))
      <meta property="og:image" content="{{ $page->og_image }}" />
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
    </style>
  </head>
  <body>
    <header class="site-header">
      <a href="/">{{ config('app.name', 'Sitewyn') }}</a>
    </header>
    <main class="page-content">
      <h1>{{ $page->title }}</h1>
      {{-- Rich text is authored by admins holding page.create/page.edit and is rendered as stored HTML. --}}
      {!! $page->content !!}
    </main>
  </body>
</html>
