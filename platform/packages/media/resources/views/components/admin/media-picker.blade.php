@php
    $currentValue = old($name, $value);
    $currentUrl = old($urlName, $urlValue);
@endphp

<div
  id="{{ $rootId }}"
  class="mb-3"
  data-media-picker
  data-media-picker-endpoint="{{ route('admin.media.picker', [], false) }}"
  data-media-picker-modal="#{{ $modalId }}"
>
  @if ($inline)
    @if ($label)
      <label class="form-label {{ $required ? 'required' : '' }}" for="{{ $fieldId }}">{{ $label }}</label>
    @endif
  @endif

  <input type="hidden" id="{{ $fieldId }}" name="{{ $name }}" value="{{ $currentValue }}" @required($required) @if ($formId) form="{{ $formId }}" @endif data-media-picker-id-input>
  <input type="hidden" id="{{ $urlFieldId }}" name="{{ $urlName }}" value="{{ $currentUrl }}" @if ($formId) form="{{ $formId }}" @endif data-media-picker-url-input>

  @if ($inline)
    <div class="row g-2 align-items-center">
      <div class="col">
        <div class="form-control d-flex align-items-center gap-3" id="{{ $previewId }}" data-media-picker-preview>
          @if ($currentUrl)
            <span class="avatar avatar-md rounded" style="background-image: url({{ $currentUrl }})"></span>
            <span class="text-truncate">{{ $currentUrl }}</span>
          @else
            <span class="avatar avatar-md bg-secondary-lt text-secondary">
              <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-photo" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <path d="M15 8h.01" />
                <path d="M3 6a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v12a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3v-12z" />
                <path d="M3 16l5 -5c.928 -.893 2.072 -.893 3 0l5 5" />
                <path d="M14 14l1 -1c.928 -.893 2.072 -.893 3 0l3 3" />
              </svg>
            </span>
            <span class="text-secondary">No media selected</span>
          @endif
        </div>
      </div>
      <div class="col-auto">
        <div class="btn-list">
          <button type="button" class="btn" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}" data-media-picker-open>
            {{ $buttonLabel }}
          </button>
          <button type="button" class="btn btn-icon" aria-label="Clear selected media" data-media-picker-clear>
            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-x" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
              <path d="M18 6l-12 12" />
              <path d="M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>
    </div>
  @endif

  <x-admin-modal id="{{ $modalId }}" title="{{ $modalTitle }}" size="lg" scrollable>
    <form class="mb-3" data-media-picker-search>
      <div class="input-icon">
        <span class="input-icon-addon">
          <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-search" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
            <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" />
            <path d="M21 21l-6 -6" />
          </svg>
        </span>
        <input type="search" class="form-control" name="q" placeholder="Search media..." aria-label="Search media">
      </div>
    </form>

    <div class="d-flex align-items-center gap-2 text-secondary mb-3" data-media-picker-breadcrumbs></div>
    <div class="row row-cards" data-media-picker-grid></div>
    <div class="empty d-none" data-media-picker-empty>
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
      <p class="empty-subtitle text-secondary">Upload files or try a different search.</p>
    </div>

    <x-slot:footer>
      <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cancel</button>
      <button type="button" class="btn btn-primary" disabled data-media-picker-use>Use selected media</button>
    </x-slot:footer>
  </x-admin-modal>
</div>

@once
  @push('scripts')
    <script>
      ;(function () {
        function icon(name) {
          if (name === 'folder') {
            return '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-folder" width="40" height="40" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 4h4l3 3h7a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-11a2 2 0 0 1 2 -2" /></svg>'
          }

          return '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file" width="48" height="48" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" /></svg>'
        }

        function escapeHtml(value) {
          return String(value || '').replace(/[&<>"']/g, function (character) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[character]
          })
        }

        let editorPickerRequest = null

        function initPicker(root) {
          const endpoint = root.getAttribute('data-media-picker-endpoint')
          const modal = root.querySelector(root.getAttribute('data-media-picker-modal'))
          const search = root.querySelector('[data-media-picker-search]')
          const breadcrumbs = root.querySelector('[data-media-picker-breadcrumbs]')
          const grid = root.querySelector('[data-media-picker-grid]')
          const empty = root.querySelector('[data-media-picker-empty]')
          const idInput = root.querySelector('[data-media-picker-id-input]')
          const urlInput = root.querySelector('[data-media-picker-url-input]')
          const preview = root.querySelector('[data-media-picker-preview]')
          const useButton = root.querySelector('[data-media-picker-use]')
          const clearButton = root.querySelector('[data-media-picker-clear]')
          let currentFolder = null
          let selectedFile = null

          function load(folderId, query) {
            const url = new URL(endpoint, window.location.origin)

            if (folderId) url.searchParams.set('folder', folderId)
            if (query) url.searchParams.set('q', query)

            grid.innerHTML = '<div class="col-12"><div class="text-secondary">Loading media...</div></div>'
            empty.classList.add('d-none')

            fetch(url, { headers: { 'Accept': 'application/json' } })
              .then(function (response) {
                return response.json()
              })
              .then(function (payload) {
                currentFolder = payload.current_folder ? payload.current_folder.id : null
                render(payload)
              })
          }

          function render(payload) {
            const items = []

            breadcrumbs.innerHTML = '<button type="button" class="btn btn-icon" data-folder=""><svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-home" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l-2 0l9 -9l9 9l-2 0" /><path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7" /><path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6" /></svg></button>'
            ;(payload.breadcrumbs || []).forEach(function (folder) {
              breadcrumbs.insertAdjacentHTML('beforeend', '<span>/</span><button type="button" class="btn btn-link p-0 text-reset" data-folder="' + folder.id + '">' + escapeHtml(folder.name) + '</button>')
            })

            ;(payload.folders || []).forEach(function (folder) {
              items.push('<div class="col-sm-6 col-lg-4 col-xl-3"><button type="button" class="card card-sm text-reset text-decoration-none h-100 w-100" data-folder="' + folder.id + '"><div class="card-body text-center"><span class="avatar avatar-xl bg-yellow-lt text-yellow mb-3">' + icon('folder') + '</span><div class="fw-medium text-truncate">' + escapeHtml(folder.name) + '</div><div class="text-secondary small text-truncate">' + escapeHtml(folder.path) + '</div></div></button></div>')
            })

            ;(payload.files || []).forEach(function (file) {
              const image = file.thumbnail
                ? '<img src="' + escapeHtml(file.thumbnail) + '" class="card-img-top object-fit-cover" style="aspect-ratio: 4 / 3;" alt="' + escapeHtml(file.name) + '">'
                : '<div class="d-flex align-items-center justify-content-center bg-secondary-lt text-secondary" style="aspect-ratio: 4 / 3;">' + icon('file') + '</div>'

              items.push('<div class="col-sm-6 col-lg-4 col-xl-3"><button type="button" class="card card-sm h-100 w-100 text-start" data-file-id="' + file.id + '" data-file-url="' + escapeHtml(file.url) + '" data-file-name="' + escapeHtml(file.name) + '" data-file-thumbnail="' + escapeHtml(file.thumbnail || '') + '">' + image + '<div class="card-body"><div class="fw-medium text-truncate">' + escapeHtml(file.name) + '</div><div class="text-secondary small text-truncate">' + escapeHtml(file.mime_type || 'Unknown type') + '</div></div></button></div>')
            })

            grid.innerHTML = items.join('')
            empty.classList.toggle('d-none', items.length > 0)
          }

          root.addEventListener('click', function (event) {
            const folderButton = event.target.closest('[data-folder]')
            const fileButton = event.target.closest('[data-file-id]')

            if (folderButton && root.contains(folderButton)) {
              load(folderButton.getAttribute('data-folder'), search.elements.q.value)

              return
            }

            if (fileButton && root.contains(fileButton)) {
              root.querySelectorAll('[data-file-id]').forEach(function (button) {
                button.classList.remove('border-primary')
              })

              fileButton.classList.add('border-primary')
              selectedFile = {
                id: fileButton.getAttribute('data-file-id'),
                url: fileButton.getAttribute('data-file-url'),
                name: fileButton.getAttribute('data-file-name'),
                thumbnail: fileButton.getAttribute('data-file-thumbnail'),
              }
              useButton && useButton.removeAttribute('disabled')

              if (editorPickerRequest) {
                editorPickerRequest.callback(selectedFile.url, { alt: selectedFile.name })
                editorPickerRequest = null
                window.tabler.bootstrap.Modal.getOrCreateInstance(modal).hide()
              }
            }
          })

          search && search.addEventListener('submit', function (event) {
            event.preventDefault()
            load(currentFolder, search.elements.q.value)
          })

          useButton && useButton.addEventListener('click', function () {
            if (! selectedFile) return

            if (idInput) {
              idInput.value = selectedFile.id
            }

            if (urlInput) {
              urlInput.value = selectedFile.url
            }
            if (preview) {
              preview.innerHTML = '<span class="avatar avatar-md rounded" style="background-image: url(' + escapeHtml(selectedFile.thumbnail || selectedFile.url) + ')"></span><span class="text-truncate">' + escapeHtml(selectedFile.name) + '</span>'
            }
            root.dispatchEvent(new CustomEvent('admin:media-picker-selected', { bubbles: true, detail: selectedFile }))
            window.tabler.bootstrap.Modal.getOrCreateInstance(modal).hide()
          })

          clearButton && clearButton.addEventListener('click', function () {
            selectedFile = null
            if (idInput) {
              idInput.value = ''
            }

            if (urlInput) {
              urlInput.value = ''
            }

            if (useButton) {
              useButton.setAttribute('disabled', 'disabled')
            }
            if (preview) {
              preview.innerHTML = '<span class="avatar avatar-md bg-secondary-lt text-secondary">' + icon('file') + '</span><span class="text-secondary">No media selected</span>'
            }
            root.dispatchEvent(new CustomEvent('admin:media-picker-cleared', { bubbles: true }))
          })

          modal && modal.addEventListener('shown.bs.modal', function () {
            load(currentFolder, search ? search.elements.q.value : '')
          })

          modal && modal.addEventListener('hidden.bs.modal', function () {
            editorPickerRequest = null
          })
        }

        function initMediaPickers() {
          document.querySelectorAll('[data-media-picker]').forEach(initPicker)
        }

        document.addEventListener('admin:editor-file-picker', function (event) {
          const detail = event.detail

          if (! detail || typeof detail.callback !== 'function') return

          const root = document.querySelector('[data-media-picker]')

          if (! root) return

          detail.handled = true
          editorPickerRequest = detail

          const modal = root.querySelector(root.getAttribute('data-media-picker-modal'))

          window.tabler.bootstrap.Modal.getOrCreateInstance(modal).show()
        })

        document.readyState !== 'loading' ? initMediaPickers() : document.addEventListener('DOMContentLoaded', initMediaPickers, { once: true })
      })()
    </script>
  @endpush
@endonce
