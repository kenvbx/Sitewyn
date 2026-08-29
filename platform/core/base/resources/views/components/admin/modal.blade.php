@php
    $dialogClasses = trim('modal-dialog ' . ($centered ? 'modal-dialog-centered ' : '') . ($scrollable ? 'modal-dialog-scrollable ' : '') . ($size ? 'modal-' . $size : ''));
@endphp

<div class="modal modal-blur fade" id="{{ $id }}" tabindex="-1" role="dialog" aria-hidden="true" @if ($staticBackdrop) data-bs-backdrop="static" data-bs-keyboard="false" @endif>
  <div class="{{ $dialogClasses }}" role="document">
    <div class="modal-content">
      @if ($title || isset($header))
        <div class="modal-header">
          @if (isset($header))
            {{ $header }}
          @else
            <h5 class="modal-title">{{ $title }}</h5>
          @endif
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
      @else
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      @endif
      <div class="modal-body">
        {{ $slot }}
      </div>
      @isset($footer)
        <div class="modal-footer">
          {{ $footer }}
        </div>
      @endisset
    </div>
  </div>
</div>
