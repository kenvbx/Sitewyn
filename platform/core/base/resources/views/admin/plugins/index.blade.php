@extends('core/base::admin.layouts.master')

@section('title', 'Plugins - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('pretitle', 'System')

@section('page-title', 'Plugins')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item active" aria-current="page">Plugins</li>
@endsection

@section('content')
  <x-admin-card
    title="Installed plugins"
    subtitle="Plugins are discovered on disk. Activation, deactivation, and dependency rules are managed here."
  >
    <div class="table-responsive">
      <table class="table table-vcenter" id="admin-plugins-table">
        <thead>
          <tr>
            <th>Plugin</th>
            <th>Description</th>
            <th>Version</th>
            <th>Source</th>
            <th>Status</th>
            <th class="w-1"></th>
          </tr>
        </thead>
        <tbody>
          @forelse ($plugins as $plugin)
            <tr>
              <td>
                <div class="fw-medium">{{ $plugin['name'] }}</div>
                <div class="small text-secondary">{{ $plugin['slug'] }}</div>
              </td>
              <td class="text-secondary">{{ $plugin['description'] ?? '—' }}</td>
              <td class="text-secondary">{{ $plugin['version'] }}</td>
              <td>
                <span class="badge {{ $plugin['source'] === 'package' ? 'bg-purple-lt' : 'bg-azure-lt' }}">{{ $plugin['source'] }}</span>
              </td>
              <td>
                @if ($plugin['isActive'])
                  <span class="badge bg-success-lt">Active</span>
                @else
                  <span class="badge bg-secondary-lt">Inactive</span>
                @endif
              </td>
              <td>
                @if ($plugin['isActive'])
                  <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#plugin-deactivate-{{ $plugin['slug'] }}" aria-label="Deactivate {{ $plugin['name'] }}">
                    Deactivate
                  </button>
                @else
                  <form method="POST" action="{{ route('admin.plugins.activate', $plugin['slug'], false) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-success" aria-label="Activate {{ $plugin['name'] }}">Activate</button>
                  </form>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center text-secondary py-5">No plugins discovered.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </x-admin-card>

  @foreach ($plugins as $plugin)
    @if ($plugin['isActive'])
      <x-admin-modal id="plugin-deactivate-{{ $plugin['slug'] }}" title="Deactivate plugin">
        <p>Deactivate <strong>{{ $plugin['name'] }}</strong> ({{ $plugin['slug'] }})?</p>
        <p class="text-secondary mb-0">
          The plugin stops loading immediately. Its data and tables are kept, so it can be
          re-activated at any time. Deactivation is blocked while an active plugin still requires it.
        </p>
        <form method="POST" action="{{ route('admin.plugins.deactivate', $plugin['slug'], false) }}" id="plugin-deactivate-form-{{ $plugin['slug'] }}">
          @csrf
        </form>

        <x-slot:footer>
          <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger" form="plugin-deactivate-form-{{ $plugin['slug'] }}">Deactivate plugin</button>
        </x-slot:footer>
      </x-admin-modal>
    @endif
  @endforeach
@endsection
