<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex, nofollow" />
    <title>{{ __('Preview') }}: {{ $post->title }}</title>
    <style>
      body {
        margin: 0;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        color: #182433;
        background: #f6f8fb;
      }

      .preview-notice {
        position: sticky;
        top: 0;
        z-index: 10;
        padding: 0.75rem 1rem;
        text-align: center;
        font-weight: 600;
        letter-spacing: 0.05em;
        color: #fff;
        background: #d63939;
      }

      .preview-watermark {
        position: fixed;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: clamp(2.5rem, 8vw, 5rem);
        font-weight: 700;
        letter-spacing: 0.1em;
        color: rgba(214, 57, 57, 0.1);
        transform: rotate(-30deg);
        pointer-events: none;
        user-select: none;
      }

      .preview-content {
        position: relative;
        max-width: 48rem;
        margin: 0 auto;
        padding: 3rem 1.5rem 4rem;
        background: #fff;
        min-height: 100vh;
      }

      .preview-content img {
        max-width: 100%;
        height: auto;
      }

      .preview-meta {
        margin: 0 0 2rem;
        font-size: 0.875rem;
        color: #626976;
      }

      .preview-featured {
        display: block;
        max-width: 100%;
        height: auto;
        margin: 0 0 2rem;
        border-radius: 4px;
      }
    </style>
  </head>
  <body>
    @if ($post->status === 'draft')
      <div class="preview-notice">PREVIEW — DRAFT</div>
      <div class="preview-watermark" aria-hidden="true">PREVIEW — DRAFT</div>
    @endif
    <main class="preview-content">
      <h1>{{ $post->title }}</h1>
      <p class="preview-meta">
        {{ __('Status') }}: {{ ucfirst($post->status) }}
        @if ($post->slug)
          &middot; /{{ $post->slug }}
        @endif
        @if ($post->category)
          &middot; {{ $post->category->name }}
        @endif
        @if ($post->tags->isNotEmpty())
          &middot; {{ $post->tags->pluck('name')->implode(', ') }}
        @endif
      </p>
      @if ($post->featured_image)
        <img src="{{ $post->featured_image }}" class="preview-featured" alt="Featured image">
      @endif
      {{-- Rich text is authored by admins holding post.create/post.edit and is rendered as stored HTML. --}}
      {!! $post->content !!}
    </main>
  </body>
</html>
