@extends('core/base::admin.layouts.master')

@section('title', 'Posts - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('pretitle', 'Content')

@section('page-title', 'Posts')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item active" aria-current="page">Posts</li>
@endsection

@php
  $canBulkDelete = auth('admin')->user()?->can('post.delete') ?? false;
  $colspan = $canBulkDelete ? 8 : 7;
@endphp

@section('page-actions')
  <div class="btn-list">
    <form action="{{ route('admin.posts.index', [], false) }}" method="get" class="d-flex gap-2">
      <div class="input-icon">
        <span class="input-icon-addon">
          <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-search" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
            <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" />
            <path d="M21 21l-6 -6" />
          </svg>
        </span>
        <input type="search" name="q" value="{{ $search }}" class="form-control" placeholder="Search posts..." aria-label="Search posts">
      </div>
      <select name="status" class="form-select w-auto" aria-label="Filter by status">
        <option value="">All statuses</option>
        <option value="draft" @selected($status === 'draft')>Draft</option>
        <option value="published" @selected($status === 'published')>Published</option>
      </select>
      <select name="category_id" class="form-select w-auto" aria-label="Filter by category">
        <option value="">All categories</option>
        @foreach ($categories as $category)
          <option value="{{ $category->id }}" @selected($categoryId === (int) $category->id)>{{ $category->name }}</option>
        @endforeach
      </select>
      <button type="submit" class="btn">Search</button>
    </form>
    @if ($canBulkDelete)
      <div class="dropdown">
        <button type="button" class="btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Bulk actions">
          Bulk actions
        </button>
        <div class="dropdown-menu">
          <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#posts-bulk-delete">Delete selected</button>
        </div>
      </div>
    @endif
    @can('post.create')
      <a href="{{ route('admin.posts.create') }}" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
          <path d="M12 5l0 14" />
          <path d="M5 12l14 0" />
        </svg>
        New post
      </a>
    @endcan
  </div>
@endsection

@section('content')
  <x-admin-data-table
    id="admin-posts-table"
    title="Post list"
    subtitle="Blog posts are visible on the site only after they are published."
    empty="No posts found."
    :empty-colspan="$colspan"
  >
    <x-slot:head>
      <tr>
        @if ($canBulkDelete)
          <th class="w-1">
            <input type="checkbox" class="form-check-input m-0" aria-label="Select all posts" data-post-bulk-select-all>
          </th>
        @endif
        <th>Title</th>
        <th>Slug</th>
        <th>Category</th>
        <th>Status</th>
        <th>Created</th>
        <th>Updated</th>
        <th class="w-1"></th>
      </tr>
    </x-slot:head>

    @forelse ($posts as $post)
      <tr>
        @if ($canBulkDelete)
          <td class="w-1">
            <input type="checkbox" class="form-check-input m-0" name="ids[]" value="{{ $post->id }}" form="admin-posts-bulk-delete-form" aria-label="Select post {{ $post->title }}">
          </td>
        @endif
        <td class="fw-medium">{{ $post->title }}</td>
        <td><span class="font-monospace text-secondary small">/blog/{{ $post->slug }}</span></td>
        <td>{{ $post->category?->name ?? '—' }}</td>
        <td>
          @if ($post->status === 'published')
            <span class="badge bg-green-lt">Published</span>
          @else
            <span class="badge bg-secondary-lt">Draft</span>
          @endif
        </td>
        <td class="text-secondary">{{ $post->created_at?->format('d M Y') }}</td>
        <td class="text-secondary">{{ $post->updated_at?->format('Y-m-d H:i') }}</td>
        <td>
          <div class="btn-list flex-nowrap">
            @can('post.edit')
              <a href="{{ route('admin.posts.edit', $post) }}" class="btn btn-icon" aria-label="Edit post">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
                  <path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" />
                  <path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" />
                  <path d="M16 5l3 3" />
                </svg>
              </a>
            @endcan
            @can('post.delete')
              <button type="button" class="btn btn-icon btn-outline-danger" data-bs-toggle="modal" data-bs-target="#post-delete-{{ $post->id }}" aria-label="Delete post">
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
        <td colspan="{{ $colspan }}" class="text-center text-secondary py-5">No posts found.</td>
      </tr>
    @endforelse
  </x-admin-data-table>

  @foreach ($posts as $post)
    @can('post.delete')
      <x-admin-modal id="post-delete-{{ $post->id }}" title="Delete post">
        <p>Delete <strong>{{ $post->title }}</strong>?</p>
        <p class="text-secondary mb-0">The post and its slug will be permanently removed.</p>
        <form method="POST" action="{{ route('admin.posts.destroy', $post, false) }}" id="post-delete-form-{{ $post->id }}">
          @csrf
          @method('DELETE')
        </form>

        <x-slot:footer>
          <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger" form="post-delete-form-{{ $post->id }}">Delete post</button>
        </x-slot:footer>
      </x-admin-modal>
    @endcan
  @endforeach

  @if ($canBulkDelete)
    <x-admin-modal id="posts-bulk-delete" title="Delete selected posts">
      <p>Delete <strong data-post-bulk-count>0</strong> selected posts?</p>
      <p class="text-secondary mb-0">The posts and their slugs will be permanently removed.</p>
      <form method="POST" action="{{ route('admin.posts.bulkDelete', [], false) }}" id="admin-posts-bulk-delete-form">
        @csrf
      </form>

      <x-slot:footer>
        <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-danger" form="admin-posts-bulk-delete-form">Delete selected</button>
      </x-slot:footer>
    </x-admin-modal>
  @endif
@endsection

@if ($canBulkDelete)
  @once
    @push('scripts')
      <script>
        ;(function () {
          var form = document.getElementById('admin-posts-bulk-delete-form')

          if (! form) return

          var selectAll = document.querySelector('[data-post-bulk-select-all]')
          var count = document.querySelector('[data-post-bulk-count]')
          var modal = document.getElementById('posts-bulk-delete')

          function selected() {
            return form.querySelectorAll('input[name="ids[]"]:checked')
          }

          if (selectAll) {
            selectAll.addEventListener('change', function () {
              form.querySelectorAll('input[name="ids[]"]').forEach(function (checkbox) {
                checkbox.checked = selectAll.checked
              })
            })
          }

          if (modal) {
            modal.addEventListener('show.bs.modal', function (event) {
              var checked = selected()

              // Nothing selected: skip the confirm modal and let the POST
              // come back with a server-side warning flash instead.
              if (checked.length === 0) {
                event.preventDefault()
                form.submit()

                return
              }

              if (count) {
                count.textContent = checked.length
              }
            })
          }
        })()
      </script>
    @endpush
  @endonce
@endif
