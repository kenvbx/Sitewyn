@extends('core/base::admin.layouts.master')

@section('title', 'Categories - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('pretitle', 'Content')

@section('page-title', 'Categories')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item active" aria-current="page">Categories</li>
@endsection

@section('page-actions')
  <div class="btn-list">
    <form action="{{ route('admin.categories.index', [], false) }}" method="get" class="d-flex gap-2">
      <div class="input-icon">
        <span class="input-icon-addon">
          <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-search" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
            <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" />
            <path d="M21 21l-6 -6" />
          </svg>
        </span>
        <input type="search" name="q" value="{{ $search }}" class="form-control" placeholder="Search categories..." aria-label="Search categories">
      </div>
      <button type="submit" class="btn">Search</button>
    </form>
    @can('category.create')
      <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
          <path d="M12 5l0 14" />
          <path d="M5 12l14 0" />
        </svg>
        New category
      </a>
    @endcan
  </div>
@endsection

@section('content')
  <x-admin-data-table
    id="admin-categories-table"
    title="Category list"
    subtitle="Categories nest under a parent; deleting a parent moves its children to the top level."
    empty="No categories found."
    :empty-colspan="5"
  >
    <x-slot:head>
      <tr>
        <th>Name</th>
        <th>Parent</th>
        <th>Posts</th>
        <th>Updated</th>
        <th class="w-1"></th>
      </tr>
    </x-slot:head>

    @forelse ($categories as $category)
      <tr>
        <td class="fw-medium">{{ $category->name }}</td>
        <td>{{ $category->parent?->name ?? '—' }}</td>
        <td>{{ $category->posts_count }}</td>
        <td class="text-secondary">{{ $category->updated_at?->format('Y-m-d H:i') }}</td>
        <td>
          <div class="btn-list flex-nowrap">
            @can('category.edit')
              <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-icon" aria-label="Edit category">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
                  <path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" />
                  <path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" />
                  <path d="M16 5l3 3" />
                </svg>
              </a>
            @endcan
            @can('category.delete')
              <button type="button" class="btn btn-icon btn-outline-danger" data-bs-toggle="modal" data-bs-target="#category-delete-{{ $category->id }}" aria-label="Delete category">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
                  <path d="M4 7l16 0" />
                  <path d="M10 11l0 6" />
                  <path d="M14 11l0 6" />
                  <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                  <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                </svg>
              </button>
            @endcan
          </div>
        </td>
      </tr>
    @empty
      <tr>
        <td colspan="5" class="text-center text-secondary py-5">No categories found.</td>
      </tr>
    @endforelse
  </x-admin-data-table>

  @foreach ($categories as $category)
    @can('category.delete')
      <x-admin-modal id="category-delete-{{ $category->id }}" title="Delete category">
        <p>Delete <strong>{{ $category->name }}</strong>?</p>
        <p class="text-secondary mb-0">Child categories move to the top level and posts in this category become uncategorized.</p>
        <form method="POST" action="{{ route('admin.categories.destroy', $category, false) }}" id="category-delete-form-{{ $category->id }}">
          @csrf
          @method('DELETE')
        </form>

        <x-slot:footer>
          <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger" form="category-delete-form-{{ $category->id }}">Delete category</button>
        </x-slot:footer>
      </x-admin-modal>
    @endcan
  @endforeach
@endsection
