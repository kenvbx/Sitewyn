<div {{ $attributes->merge(['class' => trim('alert alert-' . $type . ($important ? ' alert-important' : '') . ($dismissible ? ' alert-dismissible' : ''))]) }} role="alert">
  @if ($title)
    <div class="fw-medium">{{ $title }}</div>
  @endif
  <div>{{ $slot }}</div>
  @if ($dismissible)
    <a class="btn-close" data-bs-dismiss="alert" aria-label="Close"></a>
  @endif
</div>
