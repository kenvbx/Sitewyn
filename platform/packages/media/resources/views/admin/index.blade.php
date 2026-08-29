@extends('core/base::admin.layouts.master')

@section('title', 'Media - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('pretitle', 'Content')

@section('page-title', 'Media')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.media.index') }}">Media</a></li>
  @foreach ($breadcrumbs as $breadcrumb)
    <li class="breadcrumb-item {{ $loop->last ? 'active' : '' }}" @if ($loop->last) aria-current="page" @endif>
      @if ($loop->last)
        {{ $breadcrumb->name }}
      @else
        <a href="{{ route('admin.media.index', ['folder' => $breadcrumb->id]) }}">{{ $breadcrumb->name }}</a>
      @endif
    </li>
  @endforeach
@endsection

@push('styles')
  <link href="{{ asset('vendor/tabler/dist/libs/dropzone/dist/dropzone.css') }}" rel="stylesheet" />
@endpush

@section('page-actions')
  <div class="btn-list">
    @can('media.upload')
      <button type="button" class="btn" data-bs-toggle="modal" data-bs-target="#media-upload-modal">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-upload" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
          <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" />
          <path d="M7 9l5 -5l5 5" />
          <path d="M12 4l0 12" />
        </svg>
        Upload
      </button>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#media-folder-modal">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-folder-plus" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
          <path d="M12 19h-7a2 2 0 0 1 -2 -2v-11a2 2 0 0 1 2 -2h4l3 3h7a2 2 0 0 1 2 2v3.5" />
          <path d="M16 19h6" />
          <path d="M19 16v6" />
        </svg>
        New folder
      </button>
    @endcan
  </div>
@endsection

@section('content')
  <div class="row row-cards">
    <div class="col-12">
      <x-admin-card class="mb-0" body-class="p-0">
        <x-slot:header>
          <div>
            <h3 class="card-title">Media library</h3>
            <p class="card-subtitle">
              {{ $currentFolder ? $currentFolder->path : 'Root library' }}
            </p>
          </div>
          <div class="card-actions">
            <form action="{{ route('admin.media.index', [], false) }}" method="get" class="d-flex gap-2">
              @if ($currentFolder)
                <input type="hidden" name="folder" value="{{ $currentFolder->id }}">
              @endif
              <div class="input-icon">
                <span class="input-icon-addon">
                  <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-search" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" />
                    <path d="M21 21l-6 -6" />
                  </svg>
                </span>
                <input type="search" name="q" value="{{ $search }}" class="form-control" placeholder="Search media..." aria-label="Search media">
              </div>
              <button type="submit" class="btn">Search</button>
            </form>
          </div>
        </x-slot:header>

        <div class="card-body border-bottom py-2">
          <div class="d-flex align-items-center gap-2 text-secondary">
            <a href="{{ route('admin.media.index') }}" class="btn btn-icon" aria-label="Root media folder">
              <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-home" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <path d="M5 12l-2 0l9 -9l9 9l-2 0" />
                <path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7" />
                <path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6" />
              </svg>
            </a>
            @foreach ($breadcrumbs as $breadcrumb)
              <span>/</span>
              <a href="{{ route('admin.media.index', ['folder' => $breadcrumb->id]) }}" class="text-reset">{{ $breadcrumb->name }}</a>
            @endforeach
            @if ($search !== '')
              <span class="badge bg-blue-lt ms-auto">Search: {{ $search }}</span>
            @endif
          </div>
        </div>

        <div class="card-body">
          @if ($folders->isEmpty() && $files->isEmpty())
            <div class="empty">
              <div class="empty-icon">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-photo-off" width="48" height="48" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                  <path d="M15 8h.01" />
                  <path d="M7 3h11a3 3 0 0 1 3 3v11m-.856 3.099a2.991 2.991 0 0 1 -2.144 .901h-12a3 3 0 0 1 -3 -3v-12c0 -.845 .349 -1.608 .91 -2.153" />
                  <path d="M3 16l5 -5c.928 -.893 2.072 -.893 3 0l5 5" />
                  <path d="M16.33 12.338c.574 -.054 1.155 .166 1.67 .662l3 3" />
                  <path d="M3 3l18 18" />
                </svg>
              </div>
              <p class="empty-title">No media found</p>
              <p class="empty-subtitle text-secondary">Create a folder or upload files to start organizing the library.</p>
              @can('media.upload')
                <div class="empty-action">
                  <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#media-folder-modal">New folder</button>
                </div>
              @endcan
            </div>
          @else
            <div class="row row-cards">
              @foreach ($folders as $folder)
                <div class="col-sm-6 col-lg-3 col-xl-2">
                  <div class="card card-sm h-100">
                    @if (auth('admin')->user()?->hasAnyPermission(['media.edit', 'media.delete']))
                      <div class="card-header border-0 pb-0">
                        <div class="card-actions ms-auto">
                          <div class="dropdown">
                            <button type="button" class="btn-action" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Folder actions">
                              <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-dots-vertical" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M12 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                <path d="M12 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                <path d="M12 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                              </svg>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                              @can('media.edit')
                                <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#media-folder-rename-{{ $folder->id }}">Rename</button>
                                <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#media-folder-move-{{ $folder->id }}">Move</button>
                              @endcan
                              @can('media.delete')
                                <button type="button" class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#media-folder-delete-{{ $folder->id }}">Delete</button>
                              @endcan
                            </div>
                          </div>
                        </div>
                      </div>
                    @endif
                    <a href="{{ route('admin.media.index', ['folder' => $folder->id]) }}" class="card-body text-reset text-decoration-none text-center {{ auth('admin')->user()?->hasAnyPermission(['media.edit', 'media.delete']) ? 'pt-0' : '' }}">
                      <span class="avatar avatar-xl bg-yellow-lt text-yellow mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-folder" width="40" height="40" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                          <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                          <path d="M5 4h4l3 3h7a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-11a2 2 0 0 1 2 -2" />
                        </svg>
                      </span>
                      <div class="fw-medium text-truncate">{{ $folder->name }}</div>
                      <div class="text-secondary small text-truncate">{{ $folder->path }}</div>
                    </a>
                  </div>
                </div>
              @endforeach

              @foreach ($files as $file)
                <div class="col-sm-6 col-lg-3 col-xl-2">
                  <div class="card card-sm h-100">
                    @if (auth('admin')->user()?->hasAnyPermission(['media.edit', 'media.delete']))
                      <div class="card-header border-0 pb-0">
                        <div class="card-actions ms-auto">
                          <div class="dropdown">
                            <button type="button" class="btn-action" data-bs-toggle="dropdown" aria-expanded="false" aria-label="File actions">
                              <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-dots-vertical" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M12 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                <path d="M12 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                <path d="M12 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                              </svg>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                              @can('media.edit')
                                <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#media-file-rename-{{ $file['id'] }}">Rename</button>
                                <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#media-file-move-{{ $file['id'] }}">Move</button>
                              @endcan
                              @can('media.delete')
                                <button type="button" class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#media-file-delete-{{ $file['id'] }}">Delete</button>
                              @endcan
                            </div>
                          </div>
                        </div>
                      </div>
                    @endif
                    @if ($file['thumbnail'])
                      <a href="{{ $file['url'] }}" target="_blank" rel="noopener" class="d-block">
                        <img src="{{ $file['thumbnail'] }}" class="card-img-top object-fit-cover" style="aspect-ratio: 4 / 3;" alt="{{ $file['name'] }}">
                      </a>
                    @else
                      <a href="{{ $file['url'] }}" target="_blank" rel="noopener" class="d-flex align-items-center justify-content-center bg-secondary-lt text-secondary" style="aspect-ratio: 4 / 3;" aria-label="{{ $file['file_name'] }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file" width="48" height="48" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                          <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                          <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                          <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
                        </svg>
                      </a>
                    @endif
                    <div class="card-body text-center">
                      <div class="fw-medium text-truncate" title="{{ $file['file_name'] }}">{{ $file['name'] }}</div>
                      <div class="text-secondary small text-truncate">{{ $file['mime_type'] ?: 'Unknown type' }}</div>
                      <div class="d-flex align-items-center mt-2 text-secondary small">
                        <span>{{ \Illuminate\Support\Number::fileSize($file['size']) }}</span>
                        @if ($file['dimensions'])
                          <span class="ms-auto">{{ $file['dimensions'] }}</span>
                        @endif
                      </div>
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
          @endif
        </div>
      </x-admin-card>
    </div>
  </div>

  <x-admin-modal id="media-folder-modal" title="New folder">
    <form method="POST" action="{{ route('admin.media.folders.store', [], false) }}" id="media-folder-form" class="needs-validation" data-admin-validate novalidate>
      @csrf
      <input type="hidden" name="parent_id" value="{{ $currentFolder?->id }}">
      <x-admin-form-group
        name="name"
        label="Folder name"
        :value="old('name')"
        required
        placeholder="Images"
      />
    </form>

    <x-slot:footer>
      <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cancel</button>
      <button type="submit" class="btn btn-primary" form="media-folder-form">Create folder</button>
    </x-slot:footer>
  </x-admin-modal>

  <x-admin-modal id="media-upload-modal" title="Upload media" size="lg">
    <div class="card-tabs">
      <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item" role="presentation">
          <a href="#media-upload-local" id="media-upload-local-tab" class="nav-link active" data-bs-toggle="tab" data-bs-target="#media-upload-local" role="tab" aria-controls="media-upload-local" aria-selected="true">Upload from local</a>
        </li>
        <li class="nav-item" role="presentation">
          <a href="#media-upload-url" id="media-upload-url-tab" class="nav-link" data-bs-toggle="tab" data-bs-target="#media-upload-url" role="tab" aria-controls="media-upload-url" aria-selected="false">Upload from URL</a>
        </li>
      </ul>
      <div class="tab-content pt-3">
        <div class="tab-pane active show" id="media-upload-local" role="tabpanel" aria-labelledby="media-upload-local-tab">
          <p class="text-secondary">Drag files in or click to browse, then they will be saved in the current folder.</p>
          <form
            class="dropzone"
            id="media-dropzone"
            action="{{ route('admin.media.upload', [], false) }}"
            method="post"
            autocomplete="off"
            novalidate
            tabindex="0"
            role="button"
            aria-label="Click or drag files to upload"
            data-folder-id="{{ $currentFolder?->id }}"
          >
            @csrf
            @if ($currentFolder)
              <input type="hidden" name="folder_id" value="{{ $currentFolder->id }}">
            @endif
            <div class="fallback">
              <label class="visually-hidden" for="media-dropzone-file">Upload file</label>
              <input id="media-dropzone-file" name="file" type="file" multiple />
            </div>
            <div class="dz-message">
              <h3 class="dropzone-msg-title">Drop files here or click to upload.</h3>
              <span class="dropzone-msg-desc">Images and documents will appear in this folder after the upload finishes.</span>
            </div>
          </form>
        </div>
        <div class="tab-pane" id="media-upload-url" role="tabpanel" aria-labelledby="media-upload-url-tab">
          <form method="POST" action="{{ route('admin.media.upload', [], false) }}" id="media-url-upload-form" class="needs-validation" data-admin-validate novalidate>
            @csrf
            @if ($currentFolder)
              <input type="hidden" name="folder_id" value="{{ $currentFolder->id }}">
            @endif
            <div class="mb-3">
              <label class="form-label required" for="media-upload-url-input">File URLs</label>
              <textarea class="form-control" id="media-upload-url-input" name="upload_urls" rows="6" placeholder="https://example.com/image1.jpg&#10;https://example.com/image2.jpg&#10;https://example.com/image3.jpg" required></textarea>
              <div class="form-hint">Enter one URL per line.</div>
              <div class="invalid-feedback">Enter at least one valid file URL.</div>
            </div>
            <div class="alert alert-danger d-none" id="media-url-upload-error" role="alert"></div>
            <div class="btn-list justify-content-end">
              <button type="button" class="btn" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary" id="media-url-upload-submit">Download</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </x-admin-modal>

  @foreach ($folders as $folder)
    @can('media.edit')
      <x-admin-modal id="media-folder-rename-{{ $folder->id }}" title="Rename folder">
        <form method="POST" action="{{ route('admin.media.folders.update', $folder, false) }}" id="media-folder-rename-form-{{ $folder->id }}" class="needs-validation" data-admin-validate novalidate>
          @csrf
          @method('PATCH')
          <x-admin-form-group
            name="name"
            label="Folder name"
            :value="$folder->name"
            required
            placeholder="Folder name"
          />
        </form>

        <x-slot:footer>
          <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" form="media-folder-rename-form-{{ $folder->id }}">Save changes</button>
        </x-slot:footer>
      </x-admin-modal>

      <x-admin-modal id="media-folder-move-{{ $folder->id }}" title="Move folder">
        <form method="POST" action="{{ route('admin.media.folders.update', $folder, false) }}" id="media-folder-move-form-{{ $folder->id }}">
          @csrf
          @method('PATCH')
          <div class="mb-3">
            <label class="form-label" for="media-folder-parent-{{ $folder->id }}">Destination</label>
            <select class="form-select" id="media-folder-parent-{{ $folder->id }}" name="parent_id">
              <option value="">Root library</option>
              @foreach ($folderOptions as $option)
                @continue($option->id === $folder->id || str_starts_with((string) $option->path, (string) $folder->path.'/'))
                <option value="{{ $option->id }}" @selected($folder->parent_id === $option->id)>{{ $option->path }}</option>
              @endforeach
            </select>
          </div>
        </form>

        <x-slot:footer>
          <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" form="media-folder-move-form-{{ $folder->id }}">Move folder</button>
        </x-slot:footer>
      </x-admin-modal>
    @endcan

    @can('media.delete')
      <x-admin-modal id="media-folder-delete-{{ $folder->id }}" title="Delete folder">
        <p>Delete <strong>{{ $folder->name }}</strong> and everything inside it?</p>
        <p class="text-secondary mb-0">Files in this folder tree will also be removed from storage.</p>
        <form method="POST" action="{{ route('admin.media.folders.destroy', $folder, false) }}" id="media-folder-delete-form-{{ $folder->id }}">
          @csrf
          @method('DELETE')
        </form>

        <x-slot:footer>
          <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger" form="media-folder-delete-form-{{ $folder->id }}">Delete folder</button>
        </x-slot:footer>
      </x-admin-modal>
    @endcan
  @endforeach

  @foreach ($files as $file)
    @can('media.edit')
      <x-admin-modal id="media-file-rename-{{ $file['id'] }}" title="Rename file">
        <form method="POST" action="{{ route('admin.media.files.update', $file['id'], false) }}" id="media-file-rename-form-{{ $file['id'] }}" class="needs-validation" data-admin-validate novalidate>
          @csrf
          @method('PATCH')
          <x-admin-form-group
            name="name"
            label="File name"
            :value="$file['name']"
            required
            placeholder="File name"
          />
          <div class="form-hint">Original file: {{ $file['file_name'] }}</div>
        </form>

        <x-slot:footer>
          <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" form="media-file-rename-form-{{ $file['id'] }}">Save changes</button>
        </x-slot:footer>
      </x-admin-modal>

      <x-admin-modal id="media-file-move-{{ $file['id'] }}" title="Move file">
        <form method="POST" action="{{ route('admin.media.files.update', $file['id'], false) }}" id="media-file-move-form-{{ $file['id'] }}">
          @csrf
          @method('PATCH')
          <div class="mb-3">
            <label class="form-label" for="media-file-folder-{{ $file['id'] }}">Destination</label>
            <select class="form-select" id="media-file-folder-{{ $file['id'] }}" name="folder_id">
              <option value="">Root library</option>
              @foreach ($folderOptions as $option)
                <option value="{{ $option->id }}" @selected($file['folder_id'] === $option->id)>{{ $option->path }}</option>
              @endforeach
            </select>
          </div>
        </form>

        <x-slot:footer>
          <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" form="media-file-move-form-{{ $file['id'] }}">Move file</button>
        </x-slot:footer>
      </x-admin-modal>
    @endcan

    @can('media.delete')
      <x-admin-modal id="media-file-delete-{{ $file['id'] }}" title="Delete file">
        <p>Delete <strong>{{ $file['name'] }}</strong>?</p>
        <p class="text-secondary mb-0">The original file and generated images will be removed from storage.</p>
        <form method="POST" action="{{ route('admin.media.files.destroy', $file['id'], false) }}" id="media-file-delete-form-{{ $file['id'] }}">
          @csrf
          @method('DELETE')
        </form>

        <x-slot:footer>
          <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger" form="media-file-delete-form-{{ $file['id'] }}">Delete file</button>
        </x-slot:footer>
      </x-admin-modal>
    @endcan
  @endforeach
@endsection

@if ($errors->has('name') || $errors->has('parent_id'))
  @push('scripts')
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        var modal = document.getElementById('media-folder-modal')

        if (modal && window.tabler && window.tabler.bootstrap) {
          window.tabler.bootstrap.Modal.getOrCreateInstance(modal).show()
        }
      })
    </script>
  @endpush
@endif

@push('scripts')
  <script src="{{ asset('vendor/tabler/dist/libs/dropzone/dist/dropzone-min.js') }}" defer></script>
  <script>
    ;(function () {
      const dropzoneId = 'media-dropzone'
      const dropzoneSelector = '#media-dropzone'

      window.tabler_dropzone ??= {}

      function initDropzone() {
        const dropzoneEl = document.querySelector(dropzoneSelector)

        if (! dropzoneEl || typeof Dropzone === 'undefined') return

        Dropzone.autoDiscover = false

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        const folderId = dropzoneEl.getAttribute('data-folder-id') || ''
        let completedUploads = 0

        const instance = new Dropzone(dropzoneSelector, {
          url: dropzoneEl.action,
          method: 'post',
          paramName: 'file',
          uploadMultiple: false,
          parallelUploads: 3,
          headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
          },
        })

        window.tabler_dropzone[dropzoneId] = instance

        instance.on('sending', function (_file, _xhr, formData) {
          if (folderId) {
            formData.append('folder_id', folderId)
          }
        })

        instance.on('success', function () {
          completedUploads += 1
        })

        instance.on('error', function (file, response) {
          if (typeof response === 'string') return

          const message = response?.message || Object.values(response?.errors || {})?.flat()?.[0]

          if (message && file.previewElement) {
            file.previewElement.querySelectorAll('[data-dz-errormessage]').forEach(function (element) {
              element.textContent = message
            })
          }
        })

        instance.on('queuecomplete', function () {
          if (completedUploads > 0) {
            window.location.reload()
          }
        })

        // Mirrors Tabler's keyboard accessibility hook for the Dropzone upload target.
        dropzoneEl.addEventListener('keydown', function (event) {
          if (event.target !== dropzoneEl) return
          if (event.key !== 'Enter' && event.key !== ' ' && event.key !== 'Spacebar') return
          event.preventDefault()
          instance.hiddenFileInput?.click()
        })
      }

      document.readyState !== 'loading' ? initDropzone() : document.addEventListener('DOMContentLoaded', initDropzone, { once: true })
    })()
  </script>
  <script>
    ;(function () {
      function initUrlUpload() {
        const form = document.getElementById('media-url-upload-form')

        if (! form) return

        const error = document.getElementById('media-url-upload-error')
        const submit = document.getElementById('media-url-upload-submit')
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''

        form.addEventListener('submit', function (event) {
          event.preventDefault()
          event.stopPropagation()

          if (! form.checkValidity()) {
            form.classList.add('was-validated')

            return
          }

          error?.classList.add('d-none')
          submit?.setAttribute('disabled', 'disabled')

          fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: {
              'Accept': 'application/json',
              'X-CSRF-TOKEN': csrfToken,
            },
          })
            .then(function (response) {
              if (response.ok) return response.json()

              return response.json().then(function (payload) {
                throw payload
              })
            })
            .then(function () {
              window.location.reload()
            })
            .catch(function (payload) {
              const message = payload?.message || Object.values(payload?.errors || {})?.flat()?.[0] || 'The file could not be uploaded.'

              if (error) {
                error.textContent = message
                error.classList.remove('d-none')
              }
            })
            .finally(function () {
              submit?.removeAttribute('disabled')
            })
        })
      }

      document.readyState !== 'loading' ? initUrlUpload() : document.addEventListener('DOMContentLoaded', initUrlUpload, { once: true })
    })()
  </script>
@endpush
