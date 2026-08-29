@php
    $inputName = $multiple && ! str_ends_with($name, '[]') ? $name . '[]' : $name;
    $currentValue = old($name, $value);
    $errorBag = $errors ?? new \Illuminate\Support\ViewErrorBag;
    $inputClasses = trim('form-control ' . ($errorBag->has($name) ? 'is-invalid' : ''));
@endphp

<div class="mb-3">
  @if ($label)
    <label class="form-label {{ $required ? 'required' : '' }}" for="{{ $fieldId }}">{{ $label }}</label>
  @endif

  @if ($type === 'textarea')
    <textarea id="{{ $fieldId }}" name="{{ $inputName }}" rows="{{ $rows }}" @if ($placeholder) placeholder="{{ $placeholder }}" @endif @required($required) @if ($maxlength) maxlength="{{ $maxlength }}" @endif @if ($minlength) minlength="{{ $minlength }}" @endif @if ($pattern) pattern="{{ $pattern }}" @endif {{ $attributes->merge(['class' => $inputClasses]) }}>{{ $currentValue }}</textarea>
  @elseif ($type === 'select')
    <select id="{{ $fieldId }}" name="{{ $inputName }}" @if ($multiple) multiple @endif @required($required) {{ $attributes->merge(['class' => $inputClasses]) }}>
      @foreach ($options as $optionValue => $optionLabel)
        @php
            $selectedValues = is_array($currentValue) ? $currentValue : [$currentValue];
        @endphp
        <option value="{{ $optionValue }}" @selected(in_array((string) $optionValue, array_map('strval', $selectedValues), true))>{{ $optionLabel }}</option>
      @endforeach
    </select>
  @else
    <input type="{{ $type }}" id="{{ $fieldId }}" name="{{ $inputName }}" value="{{ $currentValue }}" @if ($placeholder) placeholder="{{ $placeholder }}" @endif @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif @required($required) @if ($maxlength) maxlength="{{ $maxlength }}" @endif @if ($minlength) minlength="{{ $minlength }}" @endif @if ($pattern) pattern="{{ $pattern }}" @endif {{ $attributes->merge(['class' => $inputClasses]) }} />
  @endif

  @if ($hint)
    <div class="form-hint">{{ $hint }}</div>
  @endif

  @if ($errorBag->has($name))
    <div class="invalid-feedback">{{ $errorBag->first($name) }}</div>
  @elseif ($invalidFeedback || $required || $minlength || $maxlength || $pattern)
    <div class="invalid-feedback">{{ $invalidFeedback ?: __('Please provide a valid value.') }}</div>
  @endif
</div>
