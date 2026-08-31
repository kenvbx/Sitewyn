@php
    // Core resolves the payload (published pages, title-ordered); this
    // theme partial owns the presentation. The optional widget heading is
    // hidden when the admin left the title empty.
    $widgetTitle = $title ?? null;
@endphp
<div class="widget widget-pages">
  @if (filled($widgetTitle))
    <h3 class="widget-title">{{ $widgetTitle }}</h3>
  @endif
  <ul class="widget-list">
    @foreach ($resolved as $page)
      <li><a href="/{{ $page->slug }}">{{ $page->title }}</a></li>
    @endforeach
  </ul>
</div>
