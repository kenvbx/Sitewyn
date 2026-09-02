<div class="card mb-4">
  <div class="card-header">
    <h2 class="card-title">{{ $title }}</h2>
  </div>
  <div class="list-group list-group-flush">
    @foreach ($rows as $row)
      <div class="list-group-item">
        <div class="d-flex align-items-center">
          <div class="me-auto">
            {{ $row['label'] }}:
            <span class="fw-medium">{{ $row['value'] }}</span>
          </div>

          @if (($row['copyable'] ?? false) === true)
            <button type="button" class="btn btn-icon btn-sm btn-ghost-primary" data-copy-value="{{ $row['value'] }}" aria-label="Copy {{ $row['label'] }}">
              @include('core/base::admin.partials.icon', ['name' => 'copy'])
            </button>
          @endif

          @if (array_key_exists('ok', $row) && $row['ok'] !== null)
            <span class="{{ $row['ok'] ? 'text-success' : 'text-danger' }} ms-2" aria-hidden="true">
              @include('core/base::admin.partials.icon', ['name' => $row['ok'] ? 'circle-check' : 'alert-circle'])
            </span>
          @endif
        </div>
      </div>
    @endforeach
  </div>
</div>

@once
  @push('scripts')
    <script>
      document.querySelectorAll('[data-copy-value]').forEach(function (button) {
        button.addEventListener('click', function () {
          navigator.clipboard.writeText(button.getAttribute('data-copy-value') || '')
        })
      })
    </script>
  @endpush
@endonce
