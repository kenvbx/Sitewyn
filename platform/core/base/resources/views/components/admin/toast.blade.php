<div {{ $attributes->merge(['class' => trim('toast ' . ($show ? 'show' : ''))]) }} id="{{ $id }}" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="{{ $autohide ? 'true' : 'false' }}">
  <div class="toast-header">
    <span class="avatar avatar-xs me-2 bg-{{ $type }}"></span>
    <strong class="me-auto">{{ $title ?: config('app.name', 'Sitewyn') }}</strong>
    @if ($time)
      <small>{{ $time }}</small>
    @endif
    <button type="button" class="ms-2 btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
  </div>
  <div class="toast-body">
    {{ $slot }}
  </div>
</div>
