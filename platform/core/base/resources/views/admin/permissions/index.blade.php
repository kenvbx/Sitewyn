@extends('core/base::admin.layouts.master')

@section('title', 'Permissions - ' . config('app.name', 'Sitewyn') . ' Admin')
@section('pretitle', 'Access Control')
@section('page-title', 'Permissions')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item active" aria-current="page">Permissions</li>
@endsection

@section('content')
  <div class="row row-cards mb-3">
    <div class="col-sm-6 col-lg-4">
      <x-admin-card>
        <div class="subheader">Permissions</div>
        <div class="h1 mb-0">{{ $permissions->count() }}</div>
      </x-admin-card>
    </div>
    <div class="col-sm-6 col-lg-4">
      <x-admin-card>
        <div class="subheader">Modules</div>
        <div class="h1 mb-0">{{ $permissionModules->count() }}</div>
      </x-admin-card>
    </div>
    <div class="col-sm-6 col-lg-4">
      <x-admin-card>
        <div class="subheader">Registered</div>
        <div class="h1 mb-0">{{ $registeredPermissions->count() }}</div>
      </x-admin-card>
    </div>
  </div>

  @forelse ($permissionModules as $module => $modulePermissions)
    <x-admin-data-table :title="$module" :subtitle="$modulePermissions->count() . ' permissions'" class="mb-3">
      <x-slot:head>
        <tr>
          <th>Permission</th>
          <th>Key</th>
          <th>Group</th>
          <th>Description</th>
        </tr>
      </x-slot:head>

      @foreach ($modulePermissions as $permission)
        <tr>
          <td class="fw-medium">{{ $permission->name }}</td>
          <td><code>{{ $permission->key }}</code></td>
          <td><span class="badge bg-blue-lt">{{ $permission->group ?: 'ungrouped' }}</span></td>
          <td class="text-secondary">{{ $permission->description ?: '-' }}</td>
        </tr>
      @endforeach
    </x-admin-data-table>
  @empty
    <div class="empty">
      <p class="empty-title">No permissions found</p>
      <p class="empty-subtitle text-secondary">Run the permission sync command or register permissions from a module provider.</p>
    </div>
  @endforelse
@endsection
