<div {{ $attributes->merge(['class' => trim('card ' . $class)]) }}>
  @if ($title || $subtitle || isset($header) || isset($actions))
    <div class="{{ trim('card-header ' . $headerClass) }}">
      @if (isset($header))
        {{ $header }}
      @else
        <div>
          @if ($title)
            <h3 class="card-title">{{ $title }}</h3>
          @endif
          @if ($subtitle)
            <p class="card-subtitle">{{ $subtitle }}</p>
          @endif
        </div>
      @endif
      @isset($actions)
        <div class="card-actions">
          {{ $actions }}
        </div>
      @endisset
    </div>
  @endif

  @if ($body)
    <div class="{{ trim('card-body ' . $bodyClass) }}">
      {{ $slot }}
    </div>
  @else
    {{ $slot }}
  @endif

  @isset($footer)
    <div class="{{ trim('card-footer ' . $footerClass) }}">
      {{ $footer }}
    </div>
  @endisset
</div>
