@php
    // Core resolves the payload (newest published posts, capped at the
    // widget's limit); this theme partial owns the presentation.
    $widgetTitle = $title ?? null;
@endphp
<div class="widget widget-recent-posts">
  @if (filled($widgetTitle))
    <h3 class="widget-title">{{ $widgetTitle }}</h3>
  @endif
  <ul class="widget-list widget-post-list">
    @foreach ($resolved as $post)
      <li>
        <a href="/blog/{{ $post->slug }}">{{ $post->title }}</a>
        <time datetime="{{ $post->updated_at->toDateString() }}">{{ $post->updated_at->format('F j, Y') }}</time>
      </li>
    @endforeach
  </ul>
</div>
