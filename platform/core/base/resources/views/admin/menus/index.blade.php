@extends('core/base::admin.layouts.master')

@section('title', 'Menus - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('pretitle', 'Content')

@section('page-title', 'Menus')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item active" aria-current="page">Menus</li>
@endsection

@section('page-actions')
  <div class="btn-list">
    <a href="{{ route('admin.menus.create') }}" class="btn btn-primary">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
        <path d="M12 5l0 14" />
        <path d="M5 12l14 0" />
      </svg>
      New menu
    </a>
  </div>
@endsection

@section('content')
  <div class="row row-cards">
    <div class="col-12">
      <x-admin-card
        title="Navigation menus"
        subtitle="Menus fill named theme locations. A menu set to the primary location replaces the default theme's automatic pages nav on every public page."
      >
        <div class="table-responsive">
          <table class="table table-vcenter" id="admin-menus-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Slug</th>
                <th>Location</th>
                <th>Items</th>
                <th class="w-1"></th>
              </tr>
            </thead>
            <tbody>
              @forelse ($menus as $menu)
                <tr>
                  <td class="fw-medium">
                    <a href="{{ route('admin.menus.edit-items', $menu) }}" class="text-reset">{{ $menu->name }}</a>
                  </td>
                  <td><code>{{ $menu->slug }}</code></td>
                  <td>
                    @if ($menu->location)
                      <span class="badge bg-green-lt">{{ $menu->location }}</span>
                    @else
                      <span class="badge bg-secondary-lt">&mdash;</span>
                    @endif
                  </td>
                  <td>{{ $menu->items_count }}</td>
                  <td>
                    <div class="btn-list flex-nowrap justify-content-end">
                      <a href="{{ route('admin.menus.edit-items', $menu) }}" class="btn btn-sm">
                        Edit items
                      </a>
                      <a href="{{ route('admin.menus.edit', $menu) }}" class="btn btn-sm">
                        Settings
                      </a>
                      <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#menu-delete-{{ $menu->id }}" aria-label="Delete menu {{ $menu->name }}">
                        Delete
                      </button>
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center text-secondary py-5">No menus found. Create one to replace the automatic pages navigation.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <x-slot:footer>
          <div class="form-hint">
            Deleting a menu removes all of its items with it. If no menu holds the primary location, the default theme falls back to listing published pages.
          </div>
        </x-slot:footer>
      </x-admin-card>
    </div>
  </div>

  @foreach ($menus as $menu)
    <x-admin-modal id="menu-delete-{{ $menu->id }}" title="Delete menu">
      <p>Delete <strong>{{ $menu->name }}</strong>?</p>
      <p class="text-secondary mb-0">All {{ $menu->items_count }} of its items are removed with it. If nothing else takes over the location, the theme falls back to the automatic pages navigation.</p>
      <form method="POST" action="{{ route('admin.menus.destroy', $menu, false) }}" id="menu-delete-form-{{ $menu->id }}">
        @csrf
      </form>

      <x-slot:footer>
        <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-danger" form="menu-delete-form-{{ $menu->id }}">Delete menu</button>
      </x-slot:footer>
    </x-admin-modal>
  @endforeach
@endsection
