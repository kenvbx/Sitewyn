@if ($paginator->hasPages())
  @if ($cardFooter)
    <div {{ $attributes->merge(['class' => trim('card-footer ' . $class)]) }}>
      {{-- Tabler is Bootstrap-based, so the server-side links use the Bootstrap view. --}}
      {{ $paginator->links('pagination::bootstrap-4') }}
    </div>
  @else
    <div {{ $attributes->merge(['class' => $class]) }}>
      {{ $paginator->links('pagination::bootstrap-4') }}
    </div>
  @endif
@endif
