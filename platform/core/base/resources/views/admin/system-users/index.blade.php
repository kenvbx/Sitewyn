@extends('core/base::admin.layouts.master')

@section('title', 'Team users - ' . config('app.name', 'Sitewyn') . ' Admin')
@section('pretitle', 'System')
@section('page-title', 'Team users')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.system') }}">Platform Administration</a></li>
  <li class="breadcrumb-item active" aria-current="page">Team users</li>
@endsection

@section('page-actions')
  @can('system.users.create')
    <a href="{{ route('admin.system.users.create') }}" class="btn btn-primary">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
        <path d="M12 5l0 14" />
        <path d="M5 12l14 0" />
      </svg>
      Create user
    </a>
  @endcan
@endsection

@section('content')
  <x-admin-data-table
    id="admin-system-users-table"
    title="Team user list"
    subtitle="Client-side search, sort, and pagination. TODO: move to server-side mode when this list grows large."
    empty="No users found."
    :empty-colspan="6"
    :value-names="['sort-name', 'sort-email', 'sort-roles', 'sort-status', ['name' => 'sort-last-login', 'attr' => 'data-date']]"
    searchable
    paginated
    :page="10"
    search-placeholder="Search users..."
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
