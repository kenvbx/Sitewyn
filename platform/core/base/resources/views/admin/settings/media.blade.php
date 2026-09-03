@extends('core/base::admin.layouts.master')

@section('title', 'Media - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.settings.edit') }}">Settings</a></li>
  <li class="breadcrumb-item active" aria-current="page">Media</li>
@endsection

@section('content')
  @php($currentDriver = old('media_driver', $settings['media_driver']))

  @if (session('status'))
    <div class="alert alert-success" role="alert">{{ session('status') }}</div>
  @endif

  <form id="media-settings-form" method="POST" action="{{ route('admin.settings.media.update', [], false) }}" class="needs-validation" data-admin-validate novalidate>
    @csrf
    @method('PUT')
    <input type="hidden" name="site_name" value="{{ $baseSettings['site_name'] }}">
    <input type="hidden" name="site_logo" value="{{ $baseSettings['site_logo'] }}">
    <input type="hidden" name="robots_txt" value="{{ $baseSettings['robots_txt'] }}">
    <input type="hidden" name="active_theme" value="{{ $baseSettings['active_theme'] }}">

    <div class="row mb-5 d-block d-md-flex">
      <div class="col-12 col-md-3">
        <h2>Media</h2>
        <p class="text-muted">Settings for media</p>
      </div>

      <div class="col-12 col-md-9">
        <div class="card">
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label" for="media-driver">Driver</label>
              <select name="media_driver" id="media-driver" class="form-select" data-media-driver-select>
                @foreach ($driverOptions as $value => $label)
                  <option value="{{ $value }}" @selected($currentDriver === $value)>{{ $label }}</option>
                @endforeach
              </select>
            </div>

            @foreach ($driverCredentialGroups as $driver => $fields)
              <div class="border rounded p-3 mb-3 @if ($currentDriver !== $driver) d-none @endif" data-media-driver-panel="{{ $driver }}">
                @foreach ($fields as $field)
                  @php($fieldType = $field['type'] ?? 'text')

                  @if ($fieldType === 'checkbox')
                    <div class="mb-3">
                      <label class="form-check">
                        <input type="hidden" name="{{ $field['key'] }}" value="0">
                        <input type="checkbox" name="{{ $field['key'] }}" value="1" class="form-check-input" @checked(old($field['key'], $settings[$field['key']]))>
                        <span class="form-check-label">{{ $field['label'] }}</span>
                      </label>

                      @if (! empty($field['hint']))
                        <div class="form-hint ms-4">{{ $field['hint'] }}</div>
                      @endif
                    </div>
                  @elseif ($fieldType === 'select')
                    <div class="mb-3">
                      <label class="form-label" for="{{ str_replace('_', '-', $field['key']) }}">{{ $field['label'] }}</label>
                      <select name="{{ $field['key'] }}" id="{{ str_replace('_', '-', $field['key']) }}" class="form-select">
                        @foreach ($field['options'] as $value => $label)
                          <option value="{{ $value }}" @selected((string) old($field['key'], $settings[$field['key']]) === (string) $value)>{{ $label }}</option>
                        @endforeach
                      </select>

                      @if (! empty($field['hint']))
                        <div class="form-hint">{{ $field['hint'] }}</div>
                      @endif
                    </div>
                  @else
                    <div class="mb-3">
                      <label class="form-label" for="{{ str_replace('_', '-', $field['key']) }}">{{ $field['label'] }}</label>
                      <input type="text" name="{{ $field['key'] }}" id="{{ str_replace('_', '-', $field['key']) }}" value="{{ old($field['key'], $settings[$field['key']]) }}" class="form-control" placeholder="{{ $field['placeholder'] ?? '' }}">

                      @if (! empty($field['hint']))
                        <div class="form-hint">{{ $field['hint'] }}</div>
                      @endif
                    </div>
                  @endif
                @endforeach
              </div>
            @endforeach

            <div class="mb-3">
              <label class="form-check">
                <input type="hidden" name="media_use_original_name_for_file_path" value="0">
                <input type="checkbox" name="media_use_original_name_for_file_path" value="1" class="form-check-input" @checked(old('media_use_original_name_for_file_path', $settings['media_use_original_name_for_file_path']))>
                <span class="form-check-label">Use original name for file path</span>
              </label>
              <div class="form-hint ms-4">When enabled, uploaded files will keep their original names. When disabled, file names will be converted to URL-friendly slugs (e.g., "My Photo.jpg" becomes "my-photo.jpg").</div>
            </div>

            <div class="mb-3">
              <label class="form-check">
                <input type="hidden" name="media_convert_file_name_to_uuid" value="0">
                <input type="checkbox" name="media_convert_file_name_to_uuid" value="1" class="form-check-input" @checked(old('media_convert_file_name_to_uuid', $settings['media_convert_file_name_to_uuid']))>
                <span class="form-check-label">Convert file name to UUID</span>
              </label>
              <div class="form-hint ms-4">When enabled, the system will convert the file name to UUID when uploading. It is useful to prevent duplicate file names and better security.</div>
            </div>

            <div class="mb-3">
              <label class="form-check">
                <input type="hidden" name="media_keep_original_file_size_quality" value="0">
                <input type="checkbox" name="media_keep_original_file_size_quality" value="1" class="form-check-input" @checked(old('media_keep_original_file_size_quality', $settings['media_keep_original_file_size_quality']))>
                <span class="form-check-label">Keep original file size and quality</span>
              </label>
              <div class="form-hint ms-4">When enabled, uploaded images will not be resized or optimized, preserving their original quality and file size. When disabled, images may be compressed and resized based on your settings.</div>
            </div>

            <div class="mb-3">
              <label class="form-label" for="media-image-quality">Image quality</label>
              <input type="number" name="media_image_quality" id="media-image-quality" value="{{ old('media_image_quality', $settings['media_image_quality']) }}" class="form-control" min="70" max="100">
              <div class="form-hint">Set the quality for image encoding (70-100). Lower values produce smaller files. Default is 75. This applies to uploaded images, thumbnails, and watermarked images.</div>
            </div>

            <div class="mb-3">
              <label class="form-check">
                <input type="hidden" name="media_turn_off_automatic_url_translation_into_latin" value="0">
                <input type="checkbox" name="media_turn_off_automatic_url_translation_into_latin" value="1" class="form-check-input" @checked(old('media_turn_off_automatic_url_translation_into_latin', $settings['media_turn_off_automatic_url_translation_into_latin']))>
                <span class="form-check-label">Turn off automatic URL translation into Latin</span>
              </label>
              <div class="form-hint ms-4">When enabled, file URLs will not be automatically transliterated to Latin characters, preserving original characters from non-Latin alphabets.</div>
            </div>

            <div class="mb-3">
              <label class="form-check">
                <input type="hidden" name="media_users_can_only_view_own_media" value="0">
                <input type="checkbox" name="media_users_can_only_view_own_media" value="1" class="form-check-input" @checked(old('media_users_can_only_view_own_media', $settings['media_users_can_only_view_own_media']))>
                <span class="form-check-label">Users can only view their own media</span>
              </label>
              <div class="form-hint ms-4">When enabled, users can only view their own media, while super admins can view all media.</div>
            </div>

            <div class="mb-3">
              <label class="form-check">
                <input type="hidden" name="media_convert_image_to_webp" value="0">
                <input type="checkbox" name="media_convert_image_to_webp" value="1" class="form-check-input" @checked(old('media_convert_image_to_webp', $settings['media_convert_image_to_webp']))>
                <span class="form-check-label">Convert JPG, JPEG, PNG image to WebP</span>
              </label>
              <div class="form-hint ms-4">WebP is a modern image format that provides superior lossless and lossy compression for images on the web. It is supported in Chrome, Firefox, Edge, and Opera. Image will be converted to WebP format when uploading. It is just applied for JPG, JPEG, PNG images.</div>
            </div>

            <div class="mb-3">
              <label class="form-label">Default placeholder image</label>
              @cannot('media.index')
                <input type="hidden" id="media-default-placeholder-image" name="media_default_placeholder_image" value="{{ old('media_default_placeholder_image', $settings['media_default_placeholder_image']) }}">
                <input type="hidden" id="media-default-placeholder-image-url" name="media_default_placeholder_image_url" value="{{ old('media_default_placeholder_image_url', $settings['media_default_placeholder_image_url']) }}">
              @endcannot

              <div class="mb-2" data-media-placeholder-preview>
                @if (old('media_default_placeholder_image_url', $settings['media_default_placeholder_image_url']))
                  <div class="avatar avatar-xl rounded" style="background-image: url({{ old('media_default_placeholder_image_url', $settings['media_default_placeholder_image_url']) }})"></div>
                @else
                  <div class="avatar avatar-xl rounded bg-secondary-lt text-secondary">
                    @include('core/base::admin.partials.icon', ['name' => 'media'])
                  </div>
                @endif
              </div>
              <template data-media-placeholder-empty-preview>
                <div class="avatar avatar-xl rounded bg-secondary-lt text-secondary">
                  @include('core/base::admin.partials.icon', ['name' => 'media'])
                </div>
              </template>

              @can('media.index')
                <button type="button" class="btn btn-link p-0" data-bs-toggle="modal" data-bs-target="#media-default-placeholder-image-media-picker-modal" data-media-placeholder-choose>Choose image</button>
              @else
                <button type="button" class="btn btn-link p-0 disabled" aria-disabled="true" data-media-placeholder-choose>Choose image</button>
              @endcan
              <span class="d-block">
                <span class="text-secondary">or </span><button type="button" class="btn btn-link p-0" data-bs-toggle="modal" data-bs-target="#media-placeholder-url-modal" data-media-placeholder-url-toggle>Add from URL</button>
              </span>
            </div>

            <div class="mb-3">
              <label class="form-label" for="media-max-upload-filesize">Max upload filesize (MB)</label>
              <input type="number" name="media_max_upload_filesize" id="media-max-upload-filesize" value="{{ old('media_max_upload_filesize', $settings['media_max_upload_filesize']) }}" class="form-control" min="1" max="2048" step="0.1">
              <div class="form-hint">Your server allows to upload files maximum {{ $settings['server_max_upload_filesize'] }}, you can change this value to limit upload filesize.</div>
            </div>

            <div class="mb-3">
              <label class="form-check">
                <input type="hidden" name="media_reduce_large_image_size" value="0">
                <input type="checkbox" name="media_reduce_large_image_size" value="1" class="form-check-input" @checked(old('media_reduce_large_image_size', $settings['media_reduce_large_image_size']))>
                <span class="form-check-label">Reduce large image size when uploading</span>
              </label>
              <div class="form-hint ms-4">When enabled, the system will reduce the size of large images when uploading, applied for JPG, JPEG, PNG, WebP image. The maximum width and height of the image will be resized to the values you set below.</div>
            </div>

            <div class="mb-3">
              <label class="form-check">
                <input type="hidden" name="media_customize_upload_path" value="0">
                <input type="checkbox" name="media_customize_upload_path" value="1" class="form-check-input" @checked(old('media_customize_upload_path', $settings['media_customize_upload_path']))>
                <span class="form-check-label">Customize upload path</span>
              </label>
              <div class="form-hint ms-4">Customize the upload path for media files. By default, the system will upload files to the "/public/storage" folder.</div>
            </div>

            <div class="mb-3">
              <label class="form-check">
                <input type="hidden" name="media_enable_chunk_upload" value="0">
                <input type="checkbox" name="media_enable_chunk_upload" value="1" class="form-check-input" @checked(old('media_enable_chunk_upload', $settings['media_enable_chunk_upload']))>
                <span class="form-check-label">Enable the chunk upload</span>
              </label>
              <div class="form-hint ms-4">Chunk size upload is used to upload large file size.</div>
            </div>

            <div class="mb-3">
              <label class="form-check">
                <input type="hidden" name="media_enable_watermark" value="0">
                <input type="checkbox" name="media_enable_watermark" value="1" class="form-check-input" @checked(old('media_enable_watermark', $settings['media_enable_watermark']))>
                <span class="form-check-label">Enable watermark</span>
              </label>
              <div class="form-hint ms-4">When enabled, a watermark will be automatically added to newly uploaded images in selected folders. This does not affect existing images.</div>
            </div>

            <div class="mb-3">
              <label class="form-label">Image processing library</label>
              <div class="form-selectgroup">
                @foreach ($imageProcessingLibraries as $value => $label)
                  <label class="form-check form-check-inline">
                    <input type="radio" name="media_image_processing_library" value="{{ $value }}" class="form-check-input" @checked(old('media_image_processing_library', $settings['media_image_processing_library']) === $value)>
                    <span class="form-check-label">{{ $label }}</span>
                  </label>
                @endforeach
              </div>
            </div>

            <div class="mb-3">
              <label class="form-check">
                <input type="hidden" name="media_enable_thumbnail_sizes" value="0">
                <input type="checkbox" name="media_enable_thumbnail_sizes" value="1" class="form-check-input" @checked(old('media_enable_thumbnail_sizes', $settings['media_enable_thumbnail_sizes'])) data-media-thumbnail-toggle>
                <span class="form-check-label">Enable thumbnail sizes</span>
              </label>
              <div class="form-hint ms-4">Enable this option to generate thumbnails for images. If it is disabled, the system will not generate thumbnails for images and always use full size image.</div>
            </div>

            <div class="border rounded p-3 mb-0 @if (! old('media_enable_thumbnail_sizes', $settings['media_enable_thumbnail_sizes'])) d-none @endif" data-media-thumbnail-panel>
              <h3 class="card-title mb-3">Media thumbnails sizes:</h3>

              @foreach ($settings['media_thumbnail_sizes'] as $key => $size)
                <div class="mb-3">
                  <label class="form-label">{{ $size['label'] }}</label>
                  <div class="row g-3">
                    <div class="col-12 col-md-6">
                      <input type="number" name="media_thumbnail_{{ $key }}_width" value="{{ old('media_thumbnail_' . $key . '_width', $size['width']) }}" class="form-control" min="0" max="5000">
                    </div>
                    <div class="col-12 col-md-6">
                      <input type="number" name="media_thumbnail_{{ $key }}_height" value="{{ old('media_thumbnail_' . $key . '_height', $size['height']) }}" class="form-control" min="0" max="5000">
                    </div>
                  </div>
                </div>
              @endforeach

              <div class="mb-3">
                <label class="form-label" for="media-thumbnail-crop-position">Thumbnail crop position</label>
                <select name="media_thumbnail_crop_position" id="media-thumbnail-crop-position" class="form-select">
                  @foreach ($thumbnailCropPositions as $value => $label)
                    <option value="{{ $value }}" @selected(old('media_thumbnail_crop_position', $settings['media_thumbnail_crop_position']) === $value)>{{ $label }}</option>
                  @endforeach
                </select>
                <div class="form-hint">This setting is used to crop the image when generating thumbnails. It will be cropped from this position until the image is filled.</div>
              </div>

              <div class="alert alert-info" role="alert">
                <div class="d-flex gap-2">
                  <div>@include('core/base::admin.partials.icon', ['name' => 'alert-circle'])</div>
                  <div>Set width or height to 0 if you just want to crop by width or height.</div>
                </div>
              </div>

              <div class="alert alert-warning mb-0" role="alert">
                <div class="d-flex gap-2">
                  <div>@include('core/base::admin.partials.icon', ['name' => 'alert-circle'])</div>
                  <div>After adjusting the thumbnail sizes, you must click on the "Generate thumbnails" button to refresh them.</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </form>

  <div class="row mb-5 d-block d-md-flex">
    <div class="col-12 col-md-3"></div>
    <div class="col-12 col-md-9">
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-lg" form="media-settings-form">
          @include('core/base::admin.partials.icon', ['name' => 'save'])
          <span class="ms-2">Save settings</span>
        </button>

        <form method="POST" action="{{ route('admin.settings.media.generate-thumbnails', [], false) }}">
          @csrf
          <button type="submit" class="btn btn-warning btn-lg text-white">Generate thumbnails</button>
        </form>
      </div>
    </div>
  </div>

  @can('media.index')
    <x-media-picker
      name="media_default_placeholder_image"
      url-name="media_default_placeholder_image_url"
      :value="old('media_default_placeholder_image', $settings['media_default_placeholder_image'])"
      :url-value="old('media_default_placeholder_image_url', $settings['media_default_placeholder_image_url'])"
      modal-title="Media gallery"
      :inline="false"
      form-id="media-settings-form"
    />
  @endcan

  <x-admin-modal id="media-placeholder-url-modal" title="Add from URL" size="md">
    <div class="mb-3">
      <label class="form-label required" for="media-placeholder-url-modal-input">URL</label>
      <input type="url" id="media-placeholder-url-modal-input" class="form-control" maxlength="2048" placeholder="https://" value="{{ old('media_default_placeholder_image_url', $settings['media_default_placeholder_image_url']) }}" data-media-placeholder-url-modal-input>
    </div>

    <x-slot:footer>
      <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cancel</button>
      <button type="button" class="btn btn-primary" data-media-placeholder-url-save>Save</button>
    </x-slot:footer>
  </x-admin-modal>
@endsection

@push('scripts')
  <script>
    ;(function () {
      var driverSelect = document.querySelector('[data-media-driver-select]')
      var driverPanels = document.querySelectorAll('[data-media-driver-panel]')

      if (driverSelect && driverPanels.length) {
        var syncDriverPanels = function () {
          driverPanels.forEach(function (panel) {
            panel.classList.toggle('d-none', panel.getAttribute('data-media-driver-panel') !== driverSelect.value)
          })
        }

        driverSelect.addEventListener('change', syncDriverPanels)
        syncDriverPanels()
      }
    })()

    ;(function () {
      var placeholderIdInput = document.getElementById('media-default-placeholder-image')
      var placeholderUrlInput = document.getElementById('media-default-placeholder-image-url')
      var placeholderUrlModal = document.getElementById('media-placeholder-url-modal')
      var placeholderUrlModalInput = document.querySelector('[data-media-placeholder-url-modal-input]')
      var placeholderUrlSave = document.querySelector('[data-media-placeholder-url-save]')
      var placeholderPreview = document.querySelector('[data-media-placeholder-preview]')
      var placeholderEmptyPreview = document.querySelector('[data-media-placeholder-empty-preview]')

      function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, function (character) {
          return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[character]
        })
      }

      function updatePlaceholderPreview(url) {
        if (!placeholderPreview) {
          return
        }

        if (!url) {
          placeholderPreview.innerHTML = placeholderEmptyPreview ? placeholderEmptyPreview.innerHTML : ''

          return
        }

        placeholderPreview.innerHTML = '<div class="avatar avatar-xl rounded" style="background-image: url(' + escapeHtml(url) + ')"></div>'
      }

      document.addEventListener('admin:media-picker-selected', function (event) {
        if (!event.target || event.target.id !== 'media-default-placeholder-image-media-picker') {
          return
        }

        var file = event.detail || {}

        if (placeholderIdInput) {
          placeholderIdInput.value = file.id || ''
        }

        if (placeholderUrlInput) {
          placeholderUrlInput.value = file.url || ''
        }

        updatePlaceholderPreview(file.thumbnail || file.url || '')
      })

      placeholderUrlSave && placeholderUrlSave.addEventListener('click', function () {
        var url = placeholderUrlModalInput ? placeholderUrlModalInput.value.trim() : ''

        if (!url || !placeholderUrlModalInput.checkValidity()) {
          placeholderUrlModalInput && placeholderUrlModalInput.reportValidity()

          return
        }

        if (placeholderIdInput) {
          placeholderIdInput.value = ''
        }

        if (placeholderUrlInput) {
          placeholderUrlInput.value = url
        }

        updatePlaceholderPreview(url)

        var bootstrap = (window.tabler && window.tabler.bootstrap) || window.bootstrap

        if (bootstrap && placeholderUrlModal) {
          bootstrap.Modal.getOrCreateInstance(placeholderUrlModal).hide()
        }
      })
    })()

    ;(function () {
      document.querySelectorAll('[data-media-thumbnail-toggle]').forEach(function (toggle) {
        var panel = document.querySelector('[data-media-thumbnail-panel]')

        if (!panel) {
          return
        }

        var syncPanel = function () {
          panel.classList.toggle('d-none', !toggle.checked)
        }

        toggle.addEventListener('change', syncPanel)
        syncPanel()
      })
    })()
  </script>
@endpush
