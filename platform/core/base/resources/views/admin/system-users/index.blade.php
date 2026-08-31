@extends('core/base::admin.layouts.master')

@section('title', 'Team users - ' . config('app.name', 'Sitewyn') . ' Admin')
@section('pretitle', 'System')
@section('page-title', 'Team users')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.system') }}">Platform Administration</a></li>
  <li class="breadcrumb-item active" aria-current="page">Team users</li>
@endsection

@php
  $activeFilterCount = ($isActive !== null ? 1 : 0) + ($role !== null ? 1 : 0)
    + ($createdFrom !== null ? 1 : 0) + ($createdTo !== null ? 1 : 0);
@endphp

@section('page-actions')
  <div class="btn-list">
    <form action="{{ route('admin.system.users.index', [], false) }}" method="get" class="d-flex gap-2" aria-label="Search and filter team users">
      <div class="input-icon">
        <span class="input-icon-addon">
          <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-search" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
            <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" />
            <path d="M21 21l-6 -6" />
          </svg>
        </span>
        <input type="search" name="q" value="{{ $search }}" class="form-control" placeholder="Search users..." aria-label="Search team users">
      </div>
      <button type="submit" class="btn">Search</button>
      <div class="dropdown">
        <button type="button" class="btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Filter team users">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon" aria-hidden="true">
            <path d="M4 4h16v2.172a2 2 0 0 1 -.586 1.414l-4.414 4.414v7l-6 2v-8.5l-4.414 -4.414a2 2 0 0 1 -.586 -1.414v-2.172z" />
          </svg>
          Filters
          @if ($activeFilterCount > 0)
            <span class="badge bg-blue-lt">{{ $activeFilterCount }}</span>
          @endif
        </button>
        <div class="dropdown-menu p-3" style="width: 280px;">
          <div class="mb-3">
            <label class="form-label" for="system-users-filter-status">Status</label>
            <select id="system-users-filter-status" name="is_active" class="form-select" aria-label="Filter by status">
              <option value="">All statuses</option>
              <option value="1" @selected($isActive === 1)>Active</option>
              <option value="0" @selected($isActive === 0)>Inactive</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label" for="system-users-filter-role">Role</label>
            <select id="system-users-filter-role" name="role" class="form-select" aria-label="Filter by role">
              <option value="">All roles</option>
              <option value="super" @selected($role === 'super')>Super Admin</option>
              @foreach ($roles as $roleOption)
                <option value="{{ $roleOption->id }}" @selected($role === (int) $roleOption->id)>{{ $roleOption->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label" for="system-users-filter-created-from">Created from</label>
            <input type="date" id="system-users-filter-created-from" name="created_from" value="{{ $createdFrom }}" class="form-control" aria-label="Filter by created from date">
          </div>
          <div class="mb-3">
            <label class="form-label" for="system-users-filter-created-to">Created to</label>
            <input type="date" id="system-users-filter-created-to" name="created_to" value="{{ $createdTo }}" class="form-control" aria-label="Filter by created to date">
          </div>
          <button type="submit" class="btn btn-primary w-100">Filter</button>
          <a href="{{ route('admin.system.users.index', [], false) }}" class="btn btn-link w-100">Clear filters</a>
        </div>
      </div>
    </form>
    @can('system.users.create')
      <a href="{{ route('admin.system.users.create') }}" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
          <path d="M12 5l0 14" />
          <path d="M5 12l14 0" />
        </svg>
        Create user
      </a>
    @endcan
  </div>
@endsection

@section('content')
  <x-admin-data-table
    id="admin-system-users-table"
    title="Team user list"
    subtitle="Search runs server-side; sort and pagination stay client-side. TODO: move to server-side mode when this list grows large."
    empty="No users found."
    :empty-colspan="6"
    :value-names="['sort-name', 'sort-email', 'sort-roles', 'sort-status', ['name' => 'sort-last-login', 'attr' => 'data-date']]"
    paginated
    :page="10"
  >
    <x-slot:head>
      <tr>
        <th><button class="table-sort" data-sort="sort-name">Name</button></th>
        <th><button class="table-sort" data-sort="sort-email">Email</button></th>
        <th><button class="table-sort" data-sort="sort-roles">Roles</button></th>
        <th><button class="table-sort" data-sort="sort-status">Status</button></th>
        <th><button class="table-sort" data-sort="sort-last-login">Last login</button></th>
        <th class="w-1"></th>
      </tr>
    </x-slot:head>

    @forelse ($users as $user)
      <tr>
        <td class="sort-name">
          <div class="d-flex align-items-center">
            <span class="avatar avatar-sm me-3">{{ collect(explode(' ', $user->name))->filter()->map(fn (string $part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))->take(2)->implode('') ?: 'AD' }}</span>
            <div>
              <div class="fw-medium">{{ $user->name }}</div>
              @if ($user->username)
                <div class="text-secondary"><code>{{ $user->username }}</code></div>
              @endif
            </div>
          </div>
        </td>
        <td class="sort-email">{{ $user->email }}</td>
        <td class="sort-roles">
          @forelse ($user->roles as $role)
            <span class="badge bg-blue-lt me-1">{{ $role->name }}</span>
          @empty
            <span class="text-secondary">No roles</span>
          @endforelse
        </td>
        <td class="sort-status">
          @if ($user->is_super_admin)
            <span class="badge bg-purple-lt">Super Admin</span>
          @endif
          @if ($user->is_active)
            <span class="badge bg-green-lt">Active</span>
          @else
            <span class="badge bg-red-lt">Locked</span>
          @endif
        </td>
        <td class="sort-last-login" data-date="{{ $user->last_login_at?->timestamp ?: 0 }}">{{ $user->last_login_at?->format('Y-m-d H:i') ?: 'Never' }}</td>
        <td>
          <div class="btn-list flex-nowrap">
            @can('system.users.edit')
              <a href="{{ route('admin.system.users.edit', $user) }}" class="btn btn-icon" aria-label="Edit user">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
                  <path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" />
                  <path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" />
                  <path d="M16 5l3 3" />
                </svg>
              </a>
            @endcan
            @can('system.users.delete')
              <form method="POST" action="{{ route('admin.system.users.destroy', $user) }}" onsubmit="return confirm('Delete this user?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-icon btn-outline-danger" aria-label="Delete user" @disabled(auth('admin')->id() === $user->id)>
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
        <td colspan="6" class="text-center text-secondary py-5">No users found.</td>
      </tr>
    @endforelse

  </x-admin-data-table>
@endsection
