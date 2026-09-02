@extends('core/base::admin.layouts.master')

@section('title', 'Request Logs - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('pretitle', 'System')

@section('page-title', 'Request Logs')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.system') }}">Platform Administration</a></li>
  <li class="breadcrumb-item active" aria-current="page">Request Logs</li>
@endsection

@section('content')
  <div class="card">
    <div class="card-header">
      <div class="row g-2 w-100 align-items-center">
        <div class="col-auto">
          <div class="dropdown">
            <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
              Bulk Actions
            </button>
            <div class="dropdown-menu">
              <button type="submit" class="dropdown-item text-danger" form="request-log-bulk-delete-form">
                Delete selected
              </button>
            </div>
          </div>
        </div>
        <div class="col-12 col-sm-4 col-md-3">
          <form method="GET" action="{{ route('admin.request-logs.index', [], false) }}">
            <div class="input-icon">
              <span class="input-icon-addon">@include('core/base::admin.partials.icon', ['name' => 'search'])</span>
              <input type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Search..." aria-label="Search request logs">
            </div>
          </form>
        </div>
        <div class="col-auto ms-auto">
          <form method="POST" action="{{ route('admin.request-logs.clear', [], false) }}" class="d-inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn">
              @include('core/base::admin.partials.icon', ['name' => 'trash'])
              Delete all records
            </button>
          </form>
        </div>
        <div class="col-auto">
          <a href="{{ route('admin.request-logs.index', [], false) }}" class="btn">
            @include('core/base::admin.partials.icon', ['name' => 'reload'])
            Reload
          </a>
        </div>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-vcenter card-table">
        <thead>
          <tr>
            <th class="w-1">
              <input class="form-check-input m-0 align-middle" type="checkbox" aria-label="Select all request logs" data-check-all="[name='ids[]']">
            </th>
            <th class="w-1">ID</th>
            <th>URL</th>
            <th class="w-1 text-nowrap">Status Code</th>
            <th class="w-1">Count</th>
            <th class="w-1 text-end">Operations</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($groups as $group)
            <tr>
              <td>
                <input class="form-check-input m-0 align-middle" type="checkbox" name="ids[]" value="{{ $group->id }}" form="request-log-bulk-delete-form" aria-label="Select request log {{ $group->id }}">
              </td>
              <td class="text-secondary">{{ $group->id }}</td>
              <td>
                <a href="{{ $group->url }}" target="_blank" rel="noopener" class="text-reset">
                  {{ $group->url }}
                  @include('core/base::admin.partials.icon', ['name' => 'external-link'])
                </a>
              </td>
              <td>{{ $group->status_code }}</td>
              <td>{{ $group->records_count }}</td>
              <td class="text-end">
                <form method="POST" action="{{ route('admin.request-logs.destroy', $group->id, false) }}">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-icon btn-danger" aria-label="Delete request log {{ $group->id }}">
                    @include('core/base::admin.partials.icon', ['name' => 'trash'])
                  </button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center text-secondary py-5">No request logs recorded yet.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="card-footer d-flex align-items-center">
      <p class="m-0 text-secondary">
        Show from {{ $groups->firstItem() ?? 0 }} to {{ $groups->lastItem() ?? 0 }} in <span class="badge bg-secondary-lt">{{ $groups->total() }}</span> records
      </p>
      <div class="ms-auto">
        <x-admin-pagination :paginator="$groups" :card-footer="false" />
      </div>
    </div>
  </div>

  <form method="POST" action="{{ route('admin.request-logs.bulk-destroy', [], false) }}" id="request-log-bulk-delete-form">
    @csrf
    @method('DELETE')
  </form>
@endsection

@push('scripts')
  <script>
    ;(function () {
      document.addEventListener('change', function (event) {
        var checkbox = event.target.closest('[data-check-all]')

        if (!checkbox) {
          return
        }

        document.querySelectorAll(checkbox.getAttribute('data-check-all')).forEach(function (item) {
          item.checked = checkbox.checked
        })
      })
    })()
  </script>
@endpush
