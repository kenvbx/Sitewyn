@php
    // Core passes the admin-authored rich text through untouched — the
    // same trust model as page content, so it renders with the shared
    // entry-content typography.
    $widgetTitle = $title ?? null;
@endphp
<div class="widget widget-text">
  @if (filled($widgetTitle))
    <h3 class="widget-title">{{ $widgetTitle }}</h3>
  @endif
  <div class="widget-content entry-content">{!! $resolved !!}</div>
</div>
