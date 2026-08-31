{{-- The default widget-area shell. The active theme owns each widget's
     presentation through its widgets/{type} partials (resolved by the
     component); this markup is only the container. An empty area renders
     nothing at all. --}}
@if ($widgets->isNotEmpty())
  <div class="widget-area widget-area-{{ $slug }}">
    @foreach ($widgets as $entry)
      @include('widgets.'.$entry['widget']->type, [
          'widget' => $entry['widget'],
          'title' => $entry['title'],
          'resolved' => $entry['resolved'],
      ])
    @endforeach
  </div>
@endif
