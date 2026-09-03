@extends('core/base::admin.layouts.master')

@section('title', 'Email Template Settings - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.settings.edit') }}">Settings</a></li>
  <li class="breadcrumb-item active" aria-current="page">Email templates</li>
@endsection

@section('content')
  @if (session('status'))
    <div class="alert alert-success" role="alert">{{ session('status') }}</div>
  @endif

  @php
    $socialLinks = collect($settings['email_template_social_links']);
    $oldSocialLabels = old('email_template_social_link_labels');
    $socialLinkRows = is_array($oldSocialLabels)
      ? collect($oldSocialLabels)->map(fn ($label, $index) => [
          'label' => $label,
          'url' => old('email_template_social_link_urls.' . $index, ''),
          'icon_image' => old('email_template_social_link_icon_images.' . $index, ''),
          'icon_url' => old('email_template_social_link_icon_urls.' . $index, ''),
      ])
      : $socialLinks;

    if ($socialLinkRows->isEmpty()) {
      $socialLinkRows = collect([['label' => '', 'url' => '', 'icon_image' => '', 'icon_url' => '']]);
    }
  @endphp

  <form id="email-template-settings-form" method="POST" action="{{ route('admin.settings.email.templates.update', [], false) }}" class="needs-validation" data-admin-validate novalidate>
    @csrf
    @method('PUT')
    <input type="hidden" name="site_name" value="{{ $baseSettings['site_name'] }}">
    <input type="hidden" name="site_logo" value="{{ $baseSettings['site_logo'] }}">
    <input type="hidden" name="robots_txt" value="{{ $baseSettings['robots_txt'] }}">
    <input type="hidden" name="active_theme" value="{{ $baseSettings['active_theme'] }}">

    <div class="row mb-5 d-block d-md-flex">
      <div class="col-12 col-md-3">
        <h2>Email Template Settings</h2>
        <p class="text-muted">View and update your email templates settings</p>
      </div>

      <div class="col-12 col-md-9">
        <div class="card">
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label" for="email-template-logo-url">Logo</label>
              @cannot('media.index')
                <input type="hidden" id="email-template-logo" name="email_template_logo" value="{{ old('email_template_logo', $settings['email_template_logo']) }}">
                <input type="hidden" id="email-template-logo-url" name="email_template_logo_url" value="{{ old('email_template_logo_url', $settings['email_template_logo_url']) }}">
              @endcannot

              <div class="mb-2" data-email-template-logo-preview>
                @if (old('email_template_logo_url', $settings['email_template_logo_url']))
                  <div class="avatar avatar-xl rounded" style="background-image: url({{ old('email_template_logo_url', $settings['email_template_logo_url']) }})"></div>
                @else
                  <div class="avatar avatar-xl rounded bg-secondary-lt text-secondary">
                    @include('core/base::admin.partials.icon', ['name' => 'media'])
                  </div>
                @endif
              </div>
              <template data-email-template-logo-empty-preview>
                <div class="avatar avatar-xl rounded bg-secondary-lt text-secondary">
                  @include('core/base::admin.partials.icon', ['name' => 'media'])
                </div>
              </template>

              @can('media.index')
                <button type="button" class="btn btn-link p-0" data-bs-toggle="modal" data-bs-target="#email-template-logo-media-picker-modal" data-email-template-logo-choose>Choose image</button>
              @else
                <button type="button" class="btn btn-link p-0 disabled" aria-disabled="true" data-email-template-logo-choose>Choose image</button>
              @endcan
              <span class="d-block">
                <button type="button" class="btn btn-link p-0" data-bs-toggle="modal" data-bs-target="#email-template-logo-url-modal" data-email-template-logo-url-toggle>or Add from URL</button>
              </span>
              <div class="form-hint">
                If don't set, it will get from theme options logo in Admin → Appearance → Theme Options → Logo.<br>
                If don't set, it will get from admin logo in Admin → Settings → Admin Appearance → Logo.<br>
                Supports only PNG, JPG, JPEG, and GIF formats.
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label" for="email-template-contact-email">Contact email address</label>
              <input type="email" name="email_template_contact_email_address" id="email-template-contact-email" value="{{ old('email_template_contact_email_address', $settings['email_template_contact_email_address']) }}" class="form-control" maxlength="255" placeholder="e.g: example@domain.com">
              <div class="form-hint">If don't set, it will get from sender email in Admin → Settings → Email</div>
            </div>

            <div class="mb-3">
              <label class="form-label" for="email-template-copyright">Copyright</label>
              <input type="text" name="email_template_copyright" id="email-template-copyright" value="{{ old('email_template_copyright', $settings['email_template_copyright']) }}" class="form-control" maxlength="500">
              <div class="form-hint">If don't set, it will get from theme options copyright in Admin → Appearance → Theme Options → General → Copyright.</div>
            </div>

            <div class="mb-3">
              <label class="form-label" for="email-template-logo-height">Logo height (px)</label>
              <input type="number" name="email_template_logo_height" id="email-template-logo-height" value="{{ old('email_template_logo_height', $settings['email_template_logo_height']) }}" class="form-control" min="1" max="500">
              <div class="form-hint">Set the height of the logo in pixels. The default value is 40px.</div>
            </div>

            <div class="mb-3">
              <label class="form-label" for="email-template-custom-css">Email template custom CSS</label>
              <div class="input-group">
                <span class="input-group-text align-items-start text-muted">1</span>
                <textarea name="email_template_custom_css" id="email-template-custom-css" class="form-control font-monospace" rows="10">{{ old('email_template_custom_css', $settings['email_template_custom_css']) }}</textarea>
              </div>
            </div>

            <div class="mb-0" data-email-template-social-links>
              <label class="form-label">Social Links</label>

              <div data-email-template-social-link-list>
                @foreach ($socialLinkRows as $link)
                  <div class="position-relative rounded border bg-body-tertiary p-3 mb-3" data-email-template-social-link-row>
                    <button type="button" class="btn-close position-absolute top-0 end-0 m-3" aria-label="Remove social link" data-email-template-social-link-remove></button>

                    <div class="mb-3 pe-4">
                      <label class="form-label">Name</label>
                      <input type="text" name="email_template_social_link_labels[]" value="{{ $link['label'] ?? '' }}" class="form-control" maxlength="120">
                    </div>

                    <div class="mb-3">
                      <label class="form-label">URL</label>
                      <input type="url" name="email_template_social_link_urls[]" value="{{ $link['url'] ?? '' }}" class="form-control" maxlength="2048">
                    </div>

                    <div class="mb-0">
                      <label class="form-label">Icon Image (Supports only PNG, JPG, JPEG, and GIF formats.)</label>
                      <input type="hidden" name="email_template_social_link_icon_images[]" value="{{ $link['icon_image'] ?? '' }}" data-email-template-social-link-icon-image>
                      <input type="hidden" name="email_template_social_link_icon_urls[]" value="{{ $link['icon_url'] ?? '' }}" data-email-template-social-link-icon-url>

                      <div class="mb-2" data-email-template-social-link-icon-preview>
                        @if ($link['icon_url'] ?? false)
                          <div class="avatar avatar-xl rounded" style="background-image: url({{ $link['icon_url'] }})"></div>
                        @else
                          <div class="avatar avatar-xl rounded bg-secondary-lt text-secondary">
                            @include('core/base::admin.partials.icon', ['name' => 'media'])
                          </div>
                        @endif
                      </div>

                      @can('media.index')
                        <button type="button" class="btn btn-link p-0" data-bs-toggle="modal" data-bs-target="#email-template-social-icon-picker-media-picker-modal" data-email-template-social-link-icon-choose>Choose image</button>
                      @else
                        <button type="button" class="btn btn-link p-0 disabled" aria-disabled="true" data-email-template-social-link-icon-choose>Choose image</button>
                      @endcan
                      <span class="d-block">
                        <span class="text-secondary">or </span><button type="button" class="btn btn-link p-0" data-bs-toggle="modal" data-bs-target="#email-template-social-icon-url-modal" data-email-template-social-link-icon-url-open>Add from URL</button>
                      </span>
                    </div>
                  </div>
                @endforeach
              </div>

              <template data-email-template-social-link-template>
                <div class="position-relative rounded border bg-body-tertiary p-3 mb-3" data-email-template-social-link-row>
                  <button type="button" class="btn-close position-absolute top-0 end-0 m-3" aria-label="Remove social link" data-email-template-social-link-remove></button>

                  <div class="mb-3 pe-4">
                    <label class="form-label">Name</label>
                    <input type="text" name="email_template_social_link_labels[]" class="form-control" maxlength="120">
                  </div>

                  <div class="mb-3">
                    <label class="form-label">URL</label>
                    <input type="url" name="email_template_social_link_urls[]" class="form-control" maxlength="2048">
                  </div>

                  <div class="mb-0">
                    <label class="form-label">Icon Image (Supports only PNG, JPG, JPEG, and GIF formats.)</label>
                    <input type="hidden" name="email_template_social_link_icon_images[]" data-email-template-social-link-icon-image>
                    <input type="hidden" name="email_template_social_link_icon_urls[]" data-email-template-social-link-icon-url>

                    <div class="mb-2" data-email-template-social-link-icon-preview>
                      <div class="avatar avatar-xl rounded bg-secondary-lt text-secondary">
                        @include('core/base::admin.partials.icon', ['name' => 'media'])
                      </div>
                    </div>

                    @can('media.index')
                      <button type="button" class="btn btn-link p-0" data-bs-toggle="modal" data-bs-target="#email-template-social-icon-picker-media-picker-modal" data-email-template-social-link-icon-choose>Choose image</button>
                    @else
                      <button type="button" class="btn btn-link p-0 disabled" aria-disabled="true" data-email-template-social-link-icon-choose>Choose image</button>
                    @endcan
                    <span class="d-block">
                      <span class="text-secondary">or </span><button type="button" class="btn btn-link p-0" data-bs-toggle="modal" data-bs-target="#email-template-social-icon-url-modal" data-email-template-social-link-icon-url-open>Add from URL</button>
                    </span>
                  </div>
                </div>
              </template>

              <button type="button" class="btn" data-email-template-social-link-add>Add new</button>
            </div>
          </div>
        </div>

        <div class="mt-4">
          <button type="submit" class="btn btn-primary btn-lg">
            @include('core/base::admin.partials.icon', ['name' => 'save'])
            <span class="ms-2">Save settings</span>
          </button>
        </div>
      </div>
    </div>
  </form>

  <div class="row mb-5 d-block d-md-flex">
    <div class="col-12 col-md-3">
      <h2>Email templates</h2>
      <p class="text-muted">Email templates using HTML & system variables.</p>
    </div>

    <div class="col-12 col-md-9">
      @foreach ($templateGroups as $group => $templates)
        <div class="card mb-4">
          <div class="card-header">
            <h2 class="card-title">{{ $group }}</h2>
          </div>
          <div class="table-responsive">
            <table class="table table-vcenter card-table">
              <thead>
                <tr>
                  <th>Template</th>
                  <th>Description</th>
                  <th class="w-1 text-end">Operations</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($templates as $template)
                  <tr id="email-template-{{ str_replace('.', '-', $template['key']) }}">
                    <td>
                      <a href="#email-template-{{ str_replace('.', '-', $template['key']) }}" class="{{ ($template['muted'] ?? false) ? 'text-decoration-line-through text-secondary' : '' }}">{{ $template['template'] }}</a>
                    </td>
                    <td>{{ $template['description'] }}</td>
                    <td class="text-end">
                      <a href="#email-template-{{ str_replace('.', '-', $template['key']) }}" class="btn btn-primary btn-icon" aria-label="Edit {{ $template['template'] }}">
                        @include('core/base::admin.partials.icon', ['name' => 'pencil'])
                      </a>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      @endforeach
    </div>
  </div>

  @can('media.index')
    <x-media-picker
      name="email_template_logo"
      url-name="email_template_logo_url"
      :value="old('email_template_logo', $settings['email_template_logo'])"
      :url-value="old('email_template_logo_url', $settings['email_template_logo_url'])"
      modal-title="Media gallery"
      :inline="false"
      form-id="email-template-settings-form"
    />

    <x-media-picker
      name="email_template_social_icon_picker"
      url-name="email_template_social_icon_picker_url"
      modal-title="Media gallery"
      :inline="false"
    />
  @endcan

  <x-admin-modal id="email-template-logo-url-modal" title="Add from URL" size="md">
    <div class="mb-3">
      <label class="form-label required" for="email-template-logo-url-modal-input">URL</label>
      <input type="url" id="email-template-logo-url-modal-input" class="form-control" maxlength="2048" placeholder="https://" value="{{ old('email_template_logo_url', $settings['email_template_logo_url']) }}" data-email-template-logo-url-modal-input>
    </div>

    <label class="form-check">
      <input type="checkbox" class="form-check-input" checked data-email-template-logo-download>
      <span class="form-check-label">Download image to local storage</span>
      <span class="form-hint">If it is unchecked, the image will be displayed from the original URL</span>
    </label>

    <x-slot:footer>
      <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cancel</button>
      <button type="button" class="btn btn-primary" data-email-template-logo-url-save>Save</button>
    </x-slot:footer>
  </x-admin-modal>

  <x-admin-modal id="email-template-social-icon-url-modal" title="Add from URL" size="md">
    <div class="mb-3">
      <label class="form-label required" for="email-template-social-icon-url-modal-input">URL</label>
      <input type="url" id="email-template-social-icon-url-modal-input" class="form-control" maxlength="2048" placeholder="https://" data-email-template-social-icon-url-modal-input>
    </div>

    <label class="form-check">
      <input type="checkbox" class="form-check-input" checked data-email-template-social-icon-download>
      <span class="form-check-label">Download image to local storage</span>
      <span class="form-hint">If it is unchecked, the image will be displayed from the original URL</span>
    </label>

    <x-slot:footer>
      <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cancel</button>
      <button type="button" class="btn btn-primary" data-email-template-social-icon-url-save>Save</button>
    </x-slot:footer>
  </x-admin-modal>
@endsection

@push('scripts')
  <script>
    ;(function () {
      var logoIdInput = document.getElementById('email-template-logo')
      var logoUrlInput = document.getElementById('email-template-logo-url')
      var logoUrlModal = document.getElementById('email-template-logo-url-modal')
      var logoUrlModalInput = document.querySelector('[data-email-template-logo-url-modal-input]')
      var logoUrlSave = document.querySelector('[data-email-template-logo-url-save]')
      var logoPreview = document.querySelector('[data-email-template-logo-preview]')
      var logoEmptyPreview = document.querySelector('[data-email-template-logo-empty-preview]')

      function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, function (character) {
          return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[character]
        })
      }

      function updateLogoPreview(url) {
        if (!logoPreview) {
          return
        }

        if (!url) {
          logoPreview.innerHTML = logoEmptyPreview ? logoEmptyPreview.innerHTML : ''

          return
        }

        logoPreview.innerHTML = '<div class="avatar avatar-xl rounded" style="background-image: url(' + escapeHtml(url) + ')"></div>'
      }

      document.addEventListener('admin:media-picker-selected', function (event) {
        if (!event.target || event.target.id !== 'email-template-logo-media-picker') {
          return
        }

        var file = event.detail || {}

        if (logoIdInput) {
          logoIdInput.value = file.id || ''
        }

        if (logoUrlInput) {
          logoUrlInput.value = file.url || ''
        }

        updateLogoPreview(file.thumbnail || file.url || '')
      })

      logoUrlSave && logoUrlSave.addEventListener('click', function () {
        var url = logoUrlModalInput ? logoUrlModalInput.value.trim() : ''

        if (!url || !logoUrlModalInput.checkValidity()) {
          logoUrlModalInput && logoUrlModalInput.reportValidity()

          return
        }

        if (logoIdInput) {
          logoIdInput.value = ''
        }

        if (logoUrlInput) {
          logoUrlInput.value = url
        }

        updateLogoPreview(url)

        var bootstrap = (window.tabler && window.tabler.bootstrap) || window.bootstrap

        if (bootstrap && logoUrlModal) {
          bootstrap.Modal.getOrCreateInstance(logoUrlModal).hide()
        }
      })
    })()

    ;(function () {
      var activeSocialLinkRow = null
      var socialIconUrlModal = document.getElementById('email-template-social-icon-url-modal')
      var socialIconUrlModalInput = document.querySelector('[data-email-template-social-icon-url-modal-input]')
      var socialIconUrlSave = document.querySelector('[data-email-template-social-icon-url-save]')
      var socialIconEmptyPreview = document.querySelector('[data-email-template-logo-empty-preview]')

      function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, function (character) {
          return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[character]
        })
      }

      function updateSocialIconPreview(row, url) {
        var preview = row.querySelector('[data-email-template-social-link-icon-preview]')

        if (!preview) {
          return
        }

        if (!url) {
          preview.innerHTML = socialIconEmptyPreview ? socialIconEmptyPreview.innerHTML : ''

          return
        }

        preview.innerHTML = '<div class="avatar avatar-xl rounded" style="background-image: url(' + escapeHtml(url) + ')"></div>'
      }

      function bindSocialLinkRow(row) {
        var removeButton = row.querySelector('[data-email-template-social-link-remove]')
        var chooseButton = row.querySelector('[data-email-template-social-link-icon-choose]')
        var urlButton = row.querySelector('[data-email-template-social-link-icon-url-open]')

        removeButton && removeButton.addEventListener('click', function () {
          row.remove()
        })

        chooseButton && chooseButton.addEventListener('click', function () {
          activeSocialLinkRow = row
        })

        urlButton && urlButton.addEventListener('click', function () {
          activeSocialLinkRow = row

          if (socialIconUrlModalInput) {
            socialIconUrlModalInput.value = row.querySelector('[data-email-template-social-link-icon-url]').value || ''
          }
        })
      }

      document.addEventListener('admin:media-picker-selected', function (event) {
        if (!activeSocialLinkRow || !event.target || event.target.id !== 'email-template-social-icon-picker-media-picker') {
          return
        }

        var file = event.detail || {}
        var iconImageInput = activeSocialLinkRow.querySelector('[data-email-template-social-link-icon-image]')
        var iconUrlInput = activeSocialLinkRow.querySelector('[data-email-template-social-link-icon-url]')

        if (iconImageInput) {
          iconImageInput.value = file.id || ''
        }

        if (iconUrlInput) {
          iconUrlInput.value = file.url || ''
        }

        updateSocialIconPreview(activeSocialLinkRow, file.thumbnail || file.url || '')
      })

      socialIconUrlSave && socialIconUrlSave.addEventListener('click', function () {
        if (!activeSocialLinkRow || !socialIconUrlModalInput) {
          return
        }

        var url = socialIconUrlModalInput.value.trim()

        if (!url || !socialIconUrlModalInput.checkValidity()) {
          socialIconUrlModalInput.reportValidity()

          return
        }

        var iconImageInput = activeSocialLinkRow.querySelector('[data-email-template-social-link-icon-image]')
        var iconUrlInput = activeSocialLinkRow.querySelector('[data-email-template-social-link-icon-url]')

        if (iconImageInput) {
          iconImageInput.value = ''
        }

        if (iconUrlInput) {
          iconUrlInput.value = url
        }

        updateSocialIconPreview(activeSocialLinkRow, url)

        var bootstrap = (window.tabler && window.tabler.bootstrap) || window.bootstrap

        if (bootstrap && socialIconUrlModal) {
          bootstrap.Modal.getOrCreateInstance(socialIconUrlModal).hide()
        }
      })

      document.querySelectorAll('[data-email-template-social-links]').forEach(function (container) {
      var list = container.querySelector('[data-email-template-social-link-list]')
      var addButton = container.querySelector('[data-email-template-social-link-add]')
      var template = container.querySelector('[data-email-template-social-link-template]')

      if (!list || !addButton || !template) {
        return
      }

      list.querySelectorAll('[data-email-template-social-link-row]').forEach(bindSocialLinkRow)

      addButton.addEventListener('click', function () {
        var row = template.content.firstElementChild.cloneNode(true)

        list.appendChild(row)
        bindSocialLinkRow(row)
        row.querySelector('input').focus()
      })
      })
    })()
  </script>
@endpush
