@extends('core/base::admin.layouts.master')

@section('title', 'Admin appearance - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.settings.edit') }}">Settings</a></li>
  <li class="breadcrumb-item active" aria-current="page">Admin appearance</li>
@endsection

@push('styles')
  <link href="{{ asset('vendor/core-base/libraries/select2/css/select2.min.css') }}" rel="stylesheet" />
  <style>
    .sitewyn-font-select + .select2-container .select2-selection--single,
    .sitewyn-admin-language-select + .select2-container .select2-selection--single {
      min-height: 38px;
      border-color: var(--tblr-border-color);
      border-radius: var(--tblr-border-radius);
    }

    .sitewyn-font-select + .select2-container .select2-selection--single .select2-selection__rendered,
    .sitewyn-admin-language-select + .select2-container .select2-selection--single .select2-selection__rendered {
      padding: .4375rem 2.25rem .4375rem .75rem;
      color: var(--tblr-body-color);
      font-size: .875rem;
      line-height: 1.4285714286;
    }

    .sitewyn-font-select + .select2-container .select2-selection--single .select2-selection__arrow,
    .sitewyn-admin-language-select + .select2-container .select2-selection--single .select2-selection__arrow {
      height: 36px;
    }

    .sitewyn-font-select + .select2-container.select2-container--focus .select2-selection--single,
    .sitewyn-font-select + .select2-container.select2-container--open .select2-selection--single,
    .sitewyn-admin-language-select + .select2-container.select2-container--focus .select2-selection--single,
    .sitewyn-admin-language-select + .select2-container.select2-container--open .select2-selection--single {
      border-color: var(--tblr-primary);
      box-shadow: 0 0 0 .25rem rgba(var(--tblr-primary-rgb), .25);
    }

    .select2-dropdown.sitewyn-font-select2-dropdown,
    .select2-dropdown.sitewyn-admin-language-select2-dropdown {
      border-color: var(--tblr-border-color);
      border-radius: var(--tblr-border-radius);
      box-shadow: var(--tblr-box-shadow-dropdown);
    }

    .sitewyn-font-select2-dropdown .select2-search--dropdown,
    .sitewyn-admin-language-select2-dropdown .select2-search--dropdown {
      padding: .5rem;
    }

    .sitewyn-font-select2-dropdown .select2-search--dropdown .select2-search__field,
    .sitewyn-admin-language-select2-dropdown .select2-search--dropdown .select2-search__field {
      min-height: 38px;
      padding: .4375rem .75rem;
      border-color: var(--tblr-border-color);
      border-radius: var(--tblr-border-radius);
      outline: 0;
    }

    .sitewyn-font-select2-dropdown .select2-results__option,
    .sitewyn-admin-language-select2-dropdown .select2-results__option {
      padding: .625rem .75rem;
      font-size: 1rem;
    }
  </style>
@endpush

@section('content')
  @php
    $fontCatalog = app(\Sitewyn\Core\Base\Support\AdminFontCatalog::class);
    $logoUrl = old('admin_logo_url', $settings['admin_logo_url']);
    $faviconUrl = old('admin_favicon_url', $settings['admin_favicon_url']);
    $selectedFontKey = old('admin_primary_font', $settings['admin_primary_font']);
    $selectedFontFamily = $selectedFont['family'] ?? $fontCatalog->family($selectedFontKey);
    $backgroundIds = old('admin_login_screen_backgrounds', $settings['admin_login_screen_backgrounds']);
    $backgroundUrls = old('admin_login_screen_background_urls', $settings['admin_login_screen_background_urls']);
    $imagePlaceholder = '<div class="avatar avatar-xl rounded bg-secondary-lt text-secondary">'.view('core/base::admin.partials.icon', ['name' => 'media'])->render().'</div>';
  @endphp

  @if (session('status'))
    <div class="alert alert-success" role="alert">{{ session('status') }}</div>
  @endif

  <form id="admin-appearance-settings-form" method="POST" action="{{ route('admin.settings.admin-appearance.update', [], false) }}" class="needs-validation" data-admin-validate novalidate>
    @csrf
    @method('PUT')
    <input type="hidden" name="site_name" value="{{ $baseSettings['site_name'] }}">
    <input type="hidden" name="site_logo" value="{{ $baseSettings['site_logo'] }}">
    <input type="hidden" name="robots_txt" value="{{ $baseSettings['robots_txt'] }}">
    <input type="hidden" name="active_theme" value="{{ $baseSettings['active_theme'] }}">

    @cannot('media.index')
      <input type="hidden" id="admin-logo" name="admin_logo" value="{{ old('admin_logo', $settings['admin_logo']) }}">
      <input type="hidden" id="admin-logo-url" name="admin_logo_url" value="{{ $logoUrl }}">
      <input type="hidden" id="admin-favicon" name="admin_favicon" value="{{ old('admin_favicon', $settings['admin_favicon']) }}">
      <input type="hidden" id="admin-favicon-url" name="admin_favicon_url" value="{{ $faviconUrl }}">
    @endcannot

    <div data-admin-background-list-inputs>
      @foreach ($backgroundIds as $backgroundId)
        <input type="hidden" name="admin_login_screen_backgrounds[]" value="{{ $backgroundId }}" data-admin-background-id-input>
      @endforeach
      @foreach ($backgroundUrls as $backgroundUrl)
        <input type="hidden" name="admin_login_screen_background_urls[]" value="{{ $backgroundUrl }}" data-admin-background-url-input>
      @endforeach
    </div>

    <div class="row mb-5 d-block d-md-flex">
      <div class="col-12 col-md-3">
        <h2>Admin appearance</h2>
        <p class="text-muted">View and update logo, favicon, layout,...</p>
      </div>

      <div class="col-12 col-md-9">
        <div class="card">
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label">Admin logo</label>
              <div class="mb-2" data-admin-image-preview="admin-logo">
                @if ($logoUrl)
                  <div class="avatar avatar-xl rounded" style="background-image: url({{ $logoUrl }})"></div>
                @else
                  {!! $imagePlaceholder !!}
                @endif
              </div>
              <template data-admin-image-empty-preview="admin-logo">{!! $imagePlaceholder !!}</template>

              @can('media.index')
                <button type="button" class="btn btn-link p-0" data-bs-toggle="modal" data-bs-target="#admin-logo-media-picker-modal">Choose image</button>
              @else
                <button type="button" class="btn btn-link p-0 disabled" aria-disabled="true">Choose image</button>
              @endcan
              <span class="d-block">
                <span class="text-secondary">or </span><button type="button" class="btn btn-link p-0" data-bs-toggle="modal" data-bs-target="#admin-logo-url-modal">Add from URL</button>
              </span>
              <div class="form-hint">Upload a custom logo to display in the admin panel header. Recommended size: 150×50px</div>
            </div>

            <div class="mb-3">
              <label class="form-label" for="admin-logo-height">Logo height (px)</label>
              <input type="number" name="admin_logo_height" id="admin-logo-height" value="{{ old('admin_logo_height', $settings['admin_logo_height']) }}" class="form-control" min="1" max="500">
              <div class="form-hint">Set the height of the logo in pixels. The default value is 32px.</div>
            </div>

            <div class="mb-3">
              <label class="form-label">Admin favicon</label>
              <div class="mb-2" data-admin-image-preview="admin-favicon">
                @if ($faviconUrl)
                  <div class="avatar avatar-xl rounded" style="background-image: url({{ $faviconUrl }})"></div>
                @else
                  {!! $imagePlaceholder !!}
                @endif
              </div>
              <template data-admin-image-empty-preview="admin-favicon">{!! $imagePlaceholder !!}</template>

              @can('media.index')
                <button type="button" class="btn btn-link p-0" data-bs-toggle="modal" data-bs-target="#admin-favicon-media-picker-modal">Choose image</button>
              @else
                <button type="button" class="btn btn-link p-0 disabled" aria-disabled="true">Choose image</button>
              @endcan
              <span class="d-block">
                <span class="text-secondary">or </span><button type="button" class="btn btn-link p-0" data-bs-toggle="modal" data-bs-target="#admin-favicon-url-modal">Add from URL</button>
              </span>
              <div class="form-hint">Upload a favicon for the admin panel. This icon appears in browser tabs and bookmarks</div>
            </div>

            <div class="mb-3">
              <label class="form-label" for="admin-favicon-type">Admin favicon type</label>
              <select name="admin_favicon_type" id="admin-favicon-type" class="form-select">
                @foreach ($faviconTypeOptions as $value => $label)
                  <option value="{{ $value }}" @selected(old('admin_favicon_type', $settings['admin_favicon_type']) === $value)>{{ $label }}</option>
                @endforeach
              </select>
              <div class="form-hint">Select the image format of your favicon. ICO format is recommended for best browser compatibility</div>
            </div>

            <div class="mb-3">
              <label class="form-label">Login screen backgrounds (~1366 × 768)</label>
              <button type="button" class="form-control d-flex flex-column align-items-center justify-content-center gap-2 text-secondary py-5" data-bs-toggle="modal" data-bs-target="#admin-login-screen-background-picker-media-picker-modal" data-admin-background-choose>
                @include('core/base::admin.partials.icon', ['name' => 'media'])
                <span>Click here to add more images.</span>
              </button>
              <div class="mt-3 @if (count($backgroundUrls) === 0) d-none @endif" data-admin-background-list>
                @foreach ($backgroundUrls as $index => $backgroundUrl)
                  <div class="d-flex align-items-center gap-3 border rounded p-2 mb-2" data-admin-background-item>
                    <span class="avatar rounded" style="background-image: url({{ $backgroundUrl }})"></span>
                    <span class="text-truncate flex-fill">{{ $backgroundUrl }}</span>
                    <button type="button" class="btn btn-icon" aria-label="Remove background" data-admin-background-remove>
                      @include('core/base::admin.partials.icon', ['name' => 'trash'])
                    </button>
                  </div>
                @endforeach
              </div>
              <button type="button" class="btn btn-link p-0 mt-2" data-bs-toggle="modal" data-bs-target="#admin-background-url-modal">Add from URL</button>
              <div class="form-hint">Upload one or more background images for the login screen. Images will rotate randomly. Recommended size: 1366×768px</div>
            </div>

            <div class="mb-3">
              <label class="form-label" for="admin-title">Admin title</label>
              <input type="text" name="admin_title" id="admin-title" value="{{ old('admin_title', $settings['admin_title']) }}" class="form-control">
              <div class="form-hint">Set the title that appears in the browser tab when viewing admin pages</div>
            </div>

            <div class="mb-3">
              <label class="form-label" for="admin-primary-font">Primary font</label>
              <select
                name="admin_primary_font"
                id="admin-primary-font"
                class="form-select sitewyn-font-select"
                data-admin-font-select
                data-font-api-url="{{ route('admin.settings.admin-appearance.google-fonts', [], false) }}"
                data-placeholder="Select a Google Font"
              >
                <option
                  value="{{ $selectedFontKey }}"
                  selected
                  data-family="{{ $selectedFontFamily }}"
                  data-stylesheet-url="{{ $fontCatalog->stylesheetUrlForFamily($selectedFontFamily) }}"
                >{{ $selectedFontFamily }}</option>
              </select>
              <div class="form-hint">Choose the primary Google Font family for the admin panel. Fonts are loaded from Google Fonts API while searching</div>
            </div>

            @foreach ([
              'admin_primary_color' => ['Primary color', 'Set the primary theme color used for buttons, links, and highlights. Default: #206bc4'],
              'admin_secondary_color' => ['Secondary color', 'Set the secondary theme color used for less prominent UI elements. Default: #6c7a91'],
              'admin_heading_color' => ['Heading color', 'Set the color for headings throughout the admin panel.'],
              'admin_text_color' => ['Text color', 'Set the default text color for the admin panel. Default: #182433'],
              'admin_link_color' => ['Link color', 'Set the color for links in the admin panel. Default: #206bc4'],
              'admin_link_hover_color' => ['Link hover color', 'Set the color for links when hovering over them. Default: #1a569d'],
            ] as $key => [$label, $hint])
              <div class="mb-3">
                <label class="form-label" for="{{ str_replace('_', '-', $key) }}">{{ $label }}</label>
                <input type="color" name="{{ $key }}" id="{{ str_replace('_', '-', $key) }}" value="{{ old($key, $settings[$key]) }}" class="form-control form-control-color">
                <div class="form-hint">{{ $hint }}</div>
              </div>
            @endforeach

            <div class="mb-3">
              <label class="form-label" for="admin-language">Admin language</label>
              <select name="admin_language" id="admin-language" class="form-select sitewyn-admin-language-select" data-admin-language-select data-placeholder="Select admin language">
                @foreach ($languageOptions as $value => $label)
                  <option value="{{ $value }}" @selected(old('admin_language', $settings['admin_language']) === $value)>{{ $label }}</option>
                @endforeach
              </select>
              <div class="form-hint">Choose the language for the admin panel interface from the full language catalog. Select "Default" to follow the site language setting</div>
            </div>

            <div class="mb-3">
              <label class="form-label">Admin language direction</label>
              <div class="form-selectgroup">
                @foreach (['ltr' => 'Left to Right', 'rtl' => 'Right to Left'] as $value => $label)
                  <label class="form-check form-check-inline">
                    <input type="radio" name="admin_language_direction" value="{{ $value }}" class="form-check-input" @checked(old('admin_language_direction', $settings['admin_language_direction']) === $value)>
                    <span class="form-check-label">{{ $label }}</span>
                  </label>
                @endforeach
              </div>
              <div class="form-hint">Set text direction for the admin panel. Choose RTL for languages like Arabic or Hebrew</div>
            </div>

            <div class="mb-3">
              <label class="form-label">Rich Editor</label>
              <div class="form-selectgroup">
                @foreach (['ckeditor' => 'CKEditor', 'tinymce' => 'TinyMCE'] as $value => $label)
                  <label class="form-check form-check-inline">
                    <input type="radio" name="admin_rich_editor" value="{{ $value }}" class="form-check-input" @checked(old('admin_rich_editor', $settings['admin_rich_editor']) === $value)>
                    <span class="form-check-label">{{ $label }}</span>
                  </label>
                @endforeach
              </div>
              <div class="form-hint">Select the default rich text editor for content creation throughout the admin panel</div>
            </div>

            <div class="mb-3">
              <label class="form-check">
                <input type="hidden" name="admin_enable_page_visual_builder" value="0">
                <input type="checkbox" name="admin_enable_page_visual_builder" value="1" class="form-check-input" @checked(old('admin_enable_page_visual_builder', $settings['admin_enable_page_visual_builder']))>
                <span class="form-check-label">Enable page visual builder</span>
              </label>
              <div class="form-hint ms-4">Allow users to build pages visually using drag-and-drop shortcodes. Disable this to hide the visual builder option</div>
            </div>

            <div class="mb-3">
              <label class="form-label">Layout</label>
              <div class="form-selectgroup">
                @foreach (['vertical' => 'Vertical', 'horizontal' => 'Horizontal'] as $value => $label)
                  <label class="form-check form-check-inline">
                    <input type="radio" name="admin_layout" value="{{ $value }}" class="form-check-input" @checked(old('admin_layout', $settings['admin_layout']) === $value)>
                    <span class="form-check-label">{{ $label }}</span>
                  </label>
                @endforeach
              </div>
              <div class="form-hint">Choose between horizontal menu (top navigation bar) or vertical menu (side navigation bar)</div>
            </div>

            <div class="mb-3">
              <label class="form-label">Container width</label>
              <div class="form-selectgroup">
                @foreach (['default' => 'Default', 'large' => 'Large', 'full' => 'Full'] as $value => $label)
                  <label class="form-check form-check-inline">
                    <input type="radio" name="admin_container_width" value="{{ $value }}" class="form-check-input" @checked(old('admin_container_width', $settings['admin_container_width']) === $value)>
                    <span class="form-check-label">{{ $label }}</span>
                  </label>
                @endforeach
              </div>
              <div class="form-hint">Set the maximum width for content containers in the admin panel</div>
            </div>

            @foreach ([
              'admin_show_menu_item_icon' => ['Show menu item icon', 'Display icons next to menu items in the admin navigation for better visual recognition'],
              'admin_show_admin_bar' => ['Show admin bar for logged-in admins, even in the front site', 'Display a quick access admin toolbar at the top of the website when logged in as an administrator'],
              'admin_show_guidelines' => ['Show guidelines', 'Display edit buttons for shortcodes (UI blocks) on the frontend when logged in as an administrator. Useful for quick editing of content blocks'],
              'admin_show_get_started_wizard' => ['Show Get Started wizard', 'Display the Get Started setup wizard on the dashboard for new installations'],
            ] as $key => [$label, $hint])
              <div class="mb-3">
                <label class="form-check">
                  <input type="hidden" name="{{ $key }}" value="0">
                  <input type="checkbox" name="{{ $key }}" value="1" class="form-check-input" @checked(old($key, $settings[$key]))>
                  <span class="form-check-label">{{ $label }}</span>
                </label>
                <div class="form-hint ms-4">{{ $hint }}</div>
              </div>
            @endforeach

            @foreach ([
              'admin_custom_css' => ['Custom CSS', 'Add custom CSS styles to override or extend the default admin panel styling'],
              'admin_header_js' => ['Header JS', 'JavaScript in the page header, wrap it inside <script></script>'],
              'admin_body_js' => ['Body JS', 'JavaScript in the page body, wrap it inside <script></script>'],
              'admin_footer_js' => ['Footer JS', 'JavaScript in the page footer, wrap it inside <script></script>'],
            ] as $key => [$label, $hint])
              <div class="mb-3">
                <label class="form-label" for="{{ str_replace('_', '-', $key) }}">{{ $label }}</label>
                <div class="input-group">
                  <span class="input-group-text align-items-start pt-2">1</span>
                  <textarea name="{{ $key }}" id="{{ str_replace('_', '-', $key) }}" rows="8" class="form-control font-monospace">{{ old($key, $settings[$key]) }}</textarea>
                </div>
                <div class="form-hint">{{ $hint }}</div>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </div>
  </form>

  <div class="row mb-5 d-block d-md-flex">
    <div class="col-12 col-md-3"></div>
    <div class="col-12 col-md-9">
      <button type="submit" class="btn btn-primary btn-lg" form="admin-appearance-settings-form">
        @include('core/base::admin.partials.icon', ['name' => 'save'])
        <span class="ms-2">Save settings</span>
      </button>
    </div>
  </div>

  @can('media.index')
    <x-media-picker
      name="admin_logo"
      url-name="admin_logo_url"
      :value="old('admin_logo', $settings['admin_logo'])"
      :url-value="$logoUrl"
      modal-title="Media gallery"
      :inline="false"
      form-id="admin-appearance-settings-form"
    />

    <x-media-picker
      name="admin_favicon"
      url-name="admin_favicon_url"
      :value="old('admin_favicon', $settings['admin_favicon'])"
      :url-value="$faviconUrl"
      modal-title="Media gallery"
      :inline="false"
      form-id="admin-appearance-settings-form"
    />

    <x-media-picker
      name="admin_login_screen_background_picker"
      url-name="admin_login_screen_background_picker_url"
      modal-title="Media gallery"
      :inline="false"
      form-id="admin-appearance-settings-form"
    />
  @endcan

  @foreach ([
    'admin-logo' => ['Admin logo URL', 'admin_logo_url', $logoUrl],
    'admin-favicon' => ['Admin favicon URL', 'admin_favicon_url', $faviconUrl],
  ] as $modalId => [$title, $field, $value])
    <x-admin-modal id="{{ $modalId }}-url-modal" title="Add from URL" size="md">
      <div class="mb-3">
        <label class="form-label required" for="{{ $modalId }}-url-modal-input">URL</label>
        <input type="url" id="{{ $modalId }}-url-modal-input" class="form-control" maxlength="2048" placeholder="https://" value="{{ $value }}" data-admin-url-modal-input="{{ $modalId }}">
      </div>

      <x-slot:footer>
        <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" data-admin-url-save="{{ $modalId }}">Save</button>
      </x-slot:footer>
    </x-admin-modal>
  @endforeach

  <x-admin-modal id="admin-background-url-modal" title="Add from URL" size="md">
    <div class="mb-3">
      <label class="form-label required" for="admin-background-url-modal-input">URLs</label>
      <textarea id="admin-background-url-modal-input" rows="6" class="form-control" placeholder="https://example.com/background-1.jpg&#10;https://example.com/background-2.jpg" data-admin-background-url-modal-input></textarea>
      <div class="form-hint">Enter one image URL per line.</div>
    </div>

    <x-slot:footer>
      <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cancel</button>
      <button type="button" class="btn btn-primary" data-admin-background-url-save>Save</button>
    </x-slot:footer>
  </x-admin-modal>
@endsection

@push('scripts')
  <script src="{{ asset('vendor/core-base/libraries/jquery.min.js') }}"></script>
  <script src="{{ asset('vendor/core-base/libraries/select2/js/select2.full.min.js') }}"></script>
  <script>
    ;(function () {
      function loadGoogleFont(stylesheetUrl) {
        if (!stylesheetUrl || document.querySelector('link[data-admin-google-font="' + stylesheetUrl + '"]')) {
          return
        }

        var link = document.createElement('link')
        link.rel = 'stylesheet'
        link.href = stylesheetUrl
        link.setAttribute('data-admin-google-font', stylesheetUrl)
        document.head.appendChild(link)
      }

      function fontFamily(data) {
        if (!data) {
          return ''
        }

        return data.family || (data.element ? data.element.getAttribute('data-family') : '') || data.text || ''
      }

      function fontStylesheet(data) {
        if (!data) {
          return ''
        }

        return data.stylesheet_url || (data.element ? data.element.getAttribute('data-stylesheet-url') : '') || ''
      }

      function renderFontOption(data) {
        var family = fontFamily(data)

        if (!family) {
          return data.text
        }

        loadGoogleFont(fontStylesheet(data))

        return window.jQuery('<span>').text(data.text || family).css('font-family', '"' + family + '", sans-serif')
      }

      var fontSelect = document.querySelector('[data-admin-font-select]')
      var adminLanguageSelect = document.querySelector('[data-admin-language-select]')

      if (fontSelect) {
        loadGoogleFont(fontSelect.options[fontSelect.selectedIndex] ? fontSelect.options[fontSelect.selectedIndex].getAttribute('data-stylesheet-url') : '')

        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
          window.jQuery(fontSelect).select2({
            ajax: {
              url: fontSelect.getAttribute('data-font-api-url'),
              dataType: 'json',
              delay: 250,
              data: function (params) {
                return {
                  q: params.term || '',
                  page: params.page || 1,
                }
              },
              processResults: function (data) {
                return data
              },
              cache: true,
            },
            dropdownCssClass: 'sitewyn-font-select2-dropdown',
            minimumInputLength: 0,
            placeholder: fontSelect.getAttribute('data-placeholder') || 'Select a Google Font',
            templateResult: renderFontOption,
            templateSelection: renderFontOption,
            width: '100%',
          })

          window.jQuery(fontSelect).on('select2:select', function (event) {
            loadGoogleFont(fontStylesheet(event.params.data))
          })
        }
      }

      if (adminLanguageSelect && window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
        window.jQuery(adminLanguageSelect).select2({
          dropdownCssClass: 'sitewyn-admin-language-select2-dropdown',
          placeholder: adminLanguageSelect.getAttribute('data-placeholder') || 'Select admin language',
          width: '100%',
        })
      }

      function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, function (character) {
          return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[character]
        })
      }

      function hideModal(modal) {
        var bootstrap = (window.tabler && window.tabler.bootstrap) || window.bootstrap

        if (bootstrap && modal) {
          bootstrap.Modal.getOrCreateInstance(modal).hide()
        }
      }

      function updateImagePreview(key, url) {
        var preview = document.querySelector('[data-admin-image-preview="' + key + '"]')
        var emptyPreview = document.querySelector('[data-admin-image-empty-preview="' + key + '"]')

        if (!preview) {
          return
        }

        preview.innerHTML = url
          ? '<div class="avatar avatar-xl rounded" style="background-image: url(' + escapeHtml(url) + ')"></div>'
          : (emptyPreview ? emptyPreview.innerHTML : '')
      }

      function setImageValues(key, id, url, previewUrl) {
        var idInput = document.getElementById(key)
        var urlInput = document.getElementById(key + '-url')

        if (idInput) {
          idInput.value = id || ''
        }

        if (urlInput) {
          urlInput.value = url || ''
        }

        updateImagePreview(key, previewUrl || url || '')
      }

      document.addEventListener('admin:media-picker-selected', function (event) {
        if (!event.target) {
          return
        }

        var file = event.detail || {}

        if (event.target.id === 'admin-logo-media-picker') {
          setImageValues('admin-logo', file.id, file.url, file.thumbnail || file.url)
        }

        if (event.target.id === 'admin-favicon-media-picker') {
          setImageValues('admin-favicon', file.id, file.url, file.thumbnail || file.url)
        }
      })

      document.querySelectorAll('[data-admin-url-save]').forEach(function (button) {
        button.addEventListener('click', function () {
          var key = button.getAttribute('data-admin-url-save')
          var input = document.querySelector('[data-admin-url-modal-input="' + key + '"]')
          var url = input ? input.value.trim() : ''

          if (!url || !input.checkValidity()) {
            input && input.reportValidity()

            return
          }

          setImageValues(key, '', url)
          hideModal(document.getElementById(key + '-url-modal'))
        })
      })

      var backgroundList = document.querySelector('[data-admin-background-list]')
      var backgroundInputs = document.querySelector('[data-admin-background-list-inputs]')
      var backgroundUrlInput = document.querySelector('[data-admin-background-url-modal-input]')
      var backgroundUrlSave = document.querySelector('[data-admin-background-url-save]')

      function appendBackground(id, url, previewUrl) {
        if (!url || !backgroundList || !backgroundInputs) {
          return
        }

        backgroundInputs.insertAdjacentHTML('beforeend', '<input type="hidden" name="admin_login_screen_backgrounds[]" value="' + escapeHtml(id || '') + '" data-admin-background-id-input><input type="hidden" name="admin_login_screen_background_urls[]" value="' + escapeHtml(url) + '" data-admin-background-url-input>')
        backgroundList.insertAdjacentHTML('beforeend', '<div class="d-flex align-items-center gap-3 border rounded p-2 mb-2" data-admin-background-item><span class="avatar rounded" style="background-image: url(' + escapeHtml(previewUrl || url) + ')"></span><span class="text-truncate flex-fill">' + escapeHtml(url) + '</span><button type="button" class="btn btn-icon" aria-label="Remove background" data-admin-background-remove><svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trash" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg></button></div>')
        backgroundList.classList.remove('d-none')
      }

      document.addEventListener('admin:media-picker-selected', function (event) {
        if (!event.target || event.target.id !== 'admin-login-screen-background-picker-media-picker') {
          return
        }

        var file = event.detail || {}
        appendBackground(file.id || '', file.url || '', file.thumbnail || file.url || '')
      })

      backgroundUrlSave && backgroundUrlSave.addEventListener('click', function () {
        var urls = backgroundUrlInput ? backgroundUrlInput.value.split(/\r?\n/).map(function (url) { return url.trim() }).filter(Boolean) : []

        if (!urls.length) {
          backgroundUrlInput && backgroundUrlInput.reportValidity()

          return
        }

        urls.forEach(function (url) {
          appendBackground('', url)
        })

        if (backgroundUrlInput) {
          backgroundUrlInput.value = ''
        }

        hideModal(document.getElementById('admin-background-url-modal'))
      })

      document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-admin-background-remove]')

        if (!button) {
          return
        }

        var item = button.closest('[data-admin-background-item]')
        var index = Array.prototype.indexOf.call(backgroundList.querySelectorAll('[data-admin-background-item]'), item)
        var idInputs = backgroundInputs.querySelectorAll('[data-admin-background-id-input]')
        var urlInputs = backgroundInputs.querySelectorAll('[data-admin-background-url-input]')

        idInputs[index] && idInputs[index].remove()
        urlInputs[index] && urlInputs[index].remove()
        item && item.remove()

        if (backgroundList.querySelectorAll('[data-admin-background-item]').length === 0) {
          backgroundList.classList.add('d-none')
        }
      })
    })()
  </script>
@endpush
