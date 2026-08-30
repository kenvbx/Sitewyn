@php
    $currentValue = old($name, $value);
@endphp

<div class="mb-3" data-admin-editor-wrapper>
  @if ($label)
    <label class="form-label" for="{{ $fieldId }}">{{ $label }}</label>
  @endif

  <textarea
    id="{{ $fieldId }}"
    name="{{ $name }}"
    class="form-control d-none"
    rows="6"
    data-admin-editor
    data-admin-editor-height="{{ $height }}"
    @if ($placeholder) data-admin-editor-placeholder="{{ $placeholder }}" @endif
    @disabled($disabled)
    {{ $attributes }}
  >{{ $currentValue }}</textarea>

  @if ($hint)
    <div class="form-hint">{{ $hint }}</div>
  @endif
</div>

@once
  @push('scripts')
    @vite(['platform/core/base/resources/js/admin.js'])
  @endpush
@endonce
