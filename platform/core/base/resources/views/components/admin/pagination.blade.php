@if ($paginator->hasPages())
  @if ($cardFooter)
    <div {{ $attributes->merge(['class' => trim('card-footer ' . $class)]) }}>
      {{ $paginator->links() }}
    </div>
  @else
    <div {{ $attributes->merge(['class' => $class]) }}>
      {{ $paginator->links() }}
    </div>
  @endif
@endif
