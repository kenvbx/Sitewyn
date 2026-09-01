@extends('core/base::admin.layouts.master')

@section('title', 'Roles - ' . config('app.name', 'Sitewyn') . ' Admin')
@section('pretitle', 'Access Control')
@section('page-title', 'Roles')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item active" aria-current="page">Roles</li>
@endsection

@section('page-actions')
  @can('roles.create')
    <a href="{{ route('admin.system.roles.create') }}" class="btn btn-primary">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
        <path d="M12 5l0 14" />
        <path d="M5 12l14 0" />
      </svg>
      Create role
    </a>
  @endcan
@endsection

@section('content')
  <x-admin-data-table
    id="admin-roles-table"
    title="Role list"
    subtitle="Client-side search, sort, and pagination. TODO: move to server-side mode when this list grows large."
    empty="No roles found."
    :empty-colspan="6"
    :value-names="['sort-name', 'sort-slug', 'sort-permissions', 'sort-users', 'sort-status']"
    searchable
    paginated
    :page="10"
    search-placeholder="Search roles..."
  >
    <x-slot:head>
      <tr>
        <th><button class="table-sort" data-sort="sort-name">Name</button></th>
        <th><button class="table-sort" data-sort="sort-slug">Slug</button></th>
        <th><button class="table-sort" data-sort="sort-permissions">Permissions</button></th>
        <th><button class="table-sort" data-sort="sort-users">Users</button></th>
        <th><button class="table-sort" data-sort="sort-status">Status</button></th>
        <th class="w-1"></th>
      </tr>
    </x-slot:head>

    @forelse ($roles as $role)
      <tr>
        <td class="sort-name">
          <div class="fw-medium">{{ $role->name }}</div>
          @if ($role->description)
            <div class="text-secondary">{{ $role->description }}</div>
          @endif
        </td>
        <td class="sort-slug"><code>{{ $role->slug }}</code></td>
        <td class="sort-permissions">{{ $role->permissions_count }}</td>
        <td class="sort-users">{{ $role->users_count }}</td>
        <td class="sort-status">
          @if ($role->is_system)
            <span class="badge bg-blue-lt">System</span>
          @else
            <span class="badge bg-green-lt">Custom</span>
          @endif
        </td>
        <td>
          <div class="btn-list flex-nowrap">
            @can('roles.edit')
              <a href="{{ route('admin.system.roles.edit', $role) }}" class="btn btn-icon" aria-label="Edit role">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
                  <path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" />
                  <path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" />
                  <path d="M16 5l3 3" />
                </svg>
              </a>
            @endcan
            @can('roles.delete')
              <form method="POST" action="{{ route('admin.system.roles.destroy', $role) }}" onsubmit="return confirm('Delete this role?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-icon btn-outline-danger" aria-label="Delete role" @disabled($role->users_count > 0 || $role->is_system)>
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
                    <path d="M4 7l16 0" />
                    <path d="M10 11l0 6" />
                    <path d="M14 11l0 6" />
                    <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                    <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                  </svg>
                </button>
              </form>
            @endcan
          </div>
        </td>
      </tr>
    @empty
      <tr>
        <td colspan="6" class="text-center text-secondary py-5">No roles found.</td>
      </tr>
    @endforelse

  </x-admin-data-table>
@endsection
