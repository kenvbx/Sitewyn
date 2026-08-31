@extends('core/base::admin.layouts.master')

@section('title', 'Audit Logs - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('pretitle', 'System')

@section('page-title', 'Audit Logs')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item active" aria-current="page">Audit Logs</li>
@endsection

@section('content')
  @php
    $badges = [
      'created' => 'bg-success-lt',
      'updated' => 'bg-info-lt',
      'deleted' => 'bg-danger-lt',
      'login' => 'bg-green-lt',
      'logout' => 'bg-secondary-lt',
      'login-failed' => 'bg-warning-lt',
    ];
  @endphp

  <x-admin-card
    title="Activity history"
    subtitle="Important admin actions are recorded with the acting user, subject, and request metadata. Entries are immutable."
  >
    <form method="GET" action="{{ route('admin.audit-logs.index', [], false) }}" class="mb-3" aria-label="Filter audit logs by action">
      <div class="row g-2 align-items-center">
        <div class="col-auto">
          <label class="form-label mb-0" for="audit-action-filter">Action</label>
        </div>
        <div class="col-auto">
          <select id="audit-action-filter" name="action" class="form-select">
            <option value="">All actions</option>
            @foreach ($actions as $action)
              <option value="{{ $action }}" @selected($action === $activeAction)>{{ $action }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-auto">
          <button type="submit" class="btn btn-outline-primary">Filter</button>
        </div>
      </div>
    </form>

    <div class="table-responsive">
      <table class="table table-vcenter" id="admin-audit-logs-table">
        <thead>
          <tr>
            <th>Time</th>
            <th>User</th>
            <th>Action</th>
            <th>Subject</th>
            <th>IP address</th>
            <th class="w-1"></th>
          </tr>
        </thead>
        <tbody>
          @forelse ($logs as $log)
            <tr>
              <td class="text-secondary">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
              <td>{{ $users->get($log->user_id, '—') }}</td>
              <td>
                <span class="badge {{ $badges[$log->action] ?? 'bg-secondary-lt' }}">{{ $log->action }}</span>
              </td>
              <td class="text-secondary">
                {{ class_basename($log->subject_type) }}{{ $log->subject_id !== null ? ' #'.$log->subject_id : '' }}
              </td>
              <td class="text-secondary">{{ $log->ip_address ?? '—' }}</td>
              <td>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#audit-log-{{ $log->id }}" aria-label="View audit entry {{ $log->id }}">
                  Details
                </button>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center text-secondary py-5">No audit entries recorded yet.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <x-slot:footer>
      <x-admin-pagination :paginator="$logs" :card-footer="false" />
    </x-slot:footer>
  </x-admin-card>

  @foreach ($logs as $log)
    <x-admin-modal id="audit-log-{{ $log->id }}" title="Audit entry #{{ $log->id }}">
      <dl class="row mb-3">
        <dt class="col-4">Time</dt>
        <dd class="col-8">{{ $log->created_at?->format('Y-m-d H:i:s') }}</dd>
        <dt class="col-4">User</dt>
        <dd class="col-8">{{ $users->get($log->user_id, '—') }}</dd>
        <dt class="col-4">Action</dt>
        <dd class="col-8">{{ $log->action }}</dd>
        <dt class="col-4">Subject</dt>
        <dd class="col-8">{{ $log->subject_type }}{{ $log->subject_id !== null ? ' #'.$log->subject_id : '' }}</dd>
        <dt class="col-4">IP address</dt>
        <dd class="col-8">{{ $log->ip_address ?? '—' }}</dd>
      </dl>

      @if (filled($log->properties))
        <pre class="m-0"><code>{{ json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</code></pre>
      @else
        <p class="text-secondary mb-0">No recorded details for this entry.</p>
      @endif

      <x-slot:footer>
        <button type="button" class="btn" data-bs-dismiss="modal">Close</button>
      </x-slot:footer>
    </x-admin-modal>
  @endforeach
@endsection
