@extends('core/base::admin.layouts.master')

@section('title', 'Tags - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('pretitle', 'Content')

@section('page-title', 'Tags')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item active" aria-current="page">Tags</li>
@endsection

@section('page-actions')
  <div class="btn-list">
    <form action="{{ route('admin.tags.index', [], false) }}" method="get" class="d-flex gap-2">
      <div class="input-icon">
        <span class="input-icon-addon">
          <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-search" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
            <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" />
            <path d="M21 21l-6 -6" />
          </svg>
        </span>
        <input type="search" name="q" value="{{ $search }}" class="form-control" placeholder="Search tags..." aria-label="Search tags">
      </div>
      <button type="submit" class="btn">Search</button>
    </form>
    @can('tag.create')
      <a href="{{ route('admin.tags.create') }}" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
          <path d="M12 5l0 14" />
          <path d="M5 12l14 0" />
        </svg>
        New tag
      </a>
    @endcan
  </div>
@endsection

@section('content')
  <x-admin-data-table
    id="admin-tags-table"
    title="Tag list"
    subtitle="Tags are a flat list; deleting a tag only detaches it from posts."
    empty="No tags found."
    :empty-colspan="5"
  >
    <x-slot:head>
      <tr>
        <th>Name</th>
        <th>Slug</th>
        <th>Posts</th>
        <th>Updated</th>
        <th class="w-1"></th>
      </tr>
    </x-slot:head>

    @forelse ($tags as $tag)
      <tr>
        <td class="fw-medium">{{ $tag->name }}</td>
        <td class="text-secondary">{{ $tag->slug }}</td>
        <td>{{ $tag->posts_count }}</td>
        <td class="text-secondary">{{ $tag->updated_at?->format('Y-m-d H:i') }}</td>
        <td>
          <div class="btn-list flex-nowrap">
            @can('tag.edit')
              <a href="{{ route('admin.tags.edit', $tag) }}" class="btn btn-icon" aria-label="Edit tag">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
                  <path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" />
                  <path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" />
                  <path d="M16 5l3 3" />
                </svg>
              </a>
            @endcan
            @can('tag.delete')
              <button type="button" class="btn btn-icon btn-outline-danger" data-bs-toggle="modal" data-bs-target="#tag-delete-{{ $tag->id }}" aria-label="Delete tag">
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
        <td colspan="5" class="text-center text-secondary py-5">No tags found.</td>
      </tr>
    @endforelse
  </x-admin-data-table>

  @foreach ($tags as $tag)
    @can('tag.delete')
      <x-admin-modal id="tag-delete-{{ $tag->id }}" title="Delete tag">
        <p>Delete <strong>{{ $tag->name }}</strong>?</p>
        <p class="text-secondary mb-0">The tag is detached from every post; the posts themselves are kept.</p>
        <form method="POST" action="{{ route('admin.tags.destroy', $tag, false) }}" id="tag-delete-form-{{ $tag->id }}">
          @csrf
          @method('DELETE')
        </form>

        <x-slot:footer>
          <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger" form="tag-delete-form-{{ $tag->id }}">Delete tag</button>
        </x-slot:footer>
      </x-admin-modal>
    @endcan
  @endforeach
@endsection
