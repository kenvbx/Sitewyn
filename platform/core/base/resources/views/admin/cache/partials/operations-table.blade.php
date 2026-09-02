<div class="table-responsive">
  <table class="table table-vcenter">
    <thead>
      <tr>
        <th class="w-1">Type</th>
        <th>Description</th>
        <th class="w-1 text-center">Action</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($rows as $row)
        <tr>
          <td>
            <span class="avatar avatar-lg bg-{{ $row['tone'] }}-lt text-{{ $row['tone'] }}">
              @include('core/base::admin.partials.icon', ['name' => $row['icon']])
            </span>
          </td>
          <td>
            <div class="fw-bold fs-3">{{ $row['title'] }}</div>
            <div class="text-muted fs-3">{{ $row['description'] }}</div>

            @if (! empty($row['meta']))
              <div class="mt-2">
                <span class="badge bg-primary-lt text-primary fs-3">
                  <span class="status-dot status-dot-animated status-blue"></span>
                  {{ $row['meta'] }}
                </span>
              </div>
            @endif
          </td>
          <td class="text-center">
            <form method="POST" action="{{ route('admin.system.cache.run', $row['operation'], false) }}">
              @csrf
              <button type="submit" class="btn btn-{{ $row['buttonTone'] }} btn-lg">
                @include('core/base::admin.partials.icon', ['name' => $row['buttonIcon']])
                <span class="ms-2">{{ $row['button'] }}</span>
              </button>
            </form>
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>
