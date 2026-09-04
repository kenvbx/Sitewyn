@extends('core/base::admin.layouts.master')

@section('title', 'Member - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.settings.edit') }}">Settings</a></li>
  <li class="breadcrumb-item active" aria-current="page">Member</li>
@endsection

@section('content')
  @php
    $allowLogin = old('member_allow_login', $settings['member_allow_login']);
    $allowRegister = old('member_allow_register', $settings['member_allow_register']);
    $avatarUrl = old('member_default_avatar_url', $settings['member_default_avatar_url']);
  @endphp

  @if (session('status'))
    <div class="alert alert-success" role="alert">{{ session('status') }}</div>
  @endif

  <form id="member-settings-form" method="POST" action="{{ route('admin.settings.members.update', [], false) }}" class="needs-validation" data-admin-validate novalidate>
    @csrf
    @method('PUT')
    <input type="hidden" name="site_name" value="{{ $baseSettings['site_name'] }}">
    <input type="hidden" name="site_logo" value="{{ $baseSettings['site_logo'] }}">
    <input type="hidden" name="robots_txt" value="{{ $baseSettings['robots_txt'] }}">
    <input type="hidden" name="active_theme" value="{{ $baseSettings['active_theme'] }}">

    <div class="row mb-5 d-block d-md-flex">
      <div class="col-12 col-md-3">
        <h2>Member</h2>
        <p class="text-muted">View and update member settings</p>
      </div>

      <div class="col-12 col-md-9">
        <div class="card">
          <div class="card-body">
            <div class="mb-4">
              <label class="form-check">
                <input type="hidden" name="member_allow_login" value="0">
                <input type="checkbox" name="member_allow_login" value="1" class="form-check-input" @checked($allowLogin)>
                <span class="form-check-label">Allow visitors to login?</span>
              </label>
              <div class="form-hint ms-4">When it is enabled, visitors can log in to your site if they have an account.</div>
            </div>

            <div class="border rounded p-4 mb-4">
              <div class="mb-4">
                <label class="form-check">
                  <input type="hidden" name="member_allow_register" value="0">
                  <input type="checkbox" name="member_allow_register" value="1" class="form-check-input" @checked($allowRegister)>
                  <span class="form-check-label">Allow visitors to register account?</span>
                </label>
                <div class="form-hint ms-4">When it is enabled, visitors can register an account on your site.</div>
              </div>

              <div class="mb-4">
                <label class="form-check">
                  <input type="hidden" name="member_verify_email" value="0">
                  <input type="checkbox" name="member_verify_email" value="1" class="form-check-input" @checked(old('member_verify_email', $settings['member_verify_email']))>
                  <span class="form-check-label">Verify account's email?</span>
                </label>
                <div class="form-hint ms-4">When it's enabled, a verification link will be sent to account's email, customers need to click on this link to verify their email before they can log in. Need to config email in Admin -> Settings -> Email to send email verification.</div>
              </div>

              <div class="mb-0">
                <label class="form-label" for="member-verification-expiration">Verification link expiration (minutes)</label>
                <input type="number" name="member_verification_expiration" id="member-verification-expiration" value="{{ old('member_verification_expiration', $settings['member_verification_expiration']) }}" class="form-control" min="1" max="10080">
                <div class="form-hint">The number of minutes that the email verification link should be considered valid. Default is 60 minutes (1 hour). Maximum is 10080 minutes (7 days).</div>
              </div>
            </div>

            <div class="mb-4">
              <label class="form-check">
                <input type="hidden" name="member_post_approval" value="0">
                <input type="checkbox" name="member_post_approval" value="1" class="form-check-input" @checked(old('member_post_approval', $settings['member_post_approval']))>
                <span class="form-check-label">Enable post approval?</span>
              </label>
              <div class="form-hint ms-4">When it is enabled, properties which posted by an agent will need to be approved by an admin before they are published and display on your site.</div>
            </div>

            <div class="mb-4">
              <label class="form-label">Default avatar</label>
              @cannot('media.index')
                <input type="hidden" id="member-default-avatar" name="member_default_avatar" value="{{ old('member_default_avatar', $settings['member_default_avatar']) }}">
                <input type="hidden" id="member-default-avatar-url" name="member_default_avatar_url" value="{{ $avatarUrl }}">
              @endcannot

              <div class="mb-2" data-member-avatar-preview>
                @if ($avatarUrl)
                  <div class="avatar avatar-xl rounded" style="background-image: url({{ $avatarUrl }})"></div>
                @else
                  <div class="avatar avatar-xl rounded bg-secondary-lt text-secondary">
                    @include('core/base::admin.partials.icon', ['name' => 'media'])
                  </div>
                @endif
              </div>
              <template data-member-avatar-empty-preview>
                <div class="avatar avatar-xl rounded bg-secondary-lt text-secondary">
                  @include('core/base::admin.partials.icon', ['name' => 'media'])
                </div>
              </template>

              @can('media.index')
                <button type="button" class="btn btn-link p-0" data-bs-toggle="modal" data-bs-target="#member-default-avatar-media-picker-modal" data-member-avatar-choose>Choose image</button>
              @else
                <button type="button" class="btn btn-link p-0 disabled" aria-disabled="true" data-member-avatar-choose>Choose image</button>
              @endcan
              <span class="d-block">
                <span class="text-secondary">or </span><button type="button" class="btn btn-link p-0" data-bs-toggle="modal" data-bs-target="#member-avatar-url-modal" data-member-avatar-url-toggle>Add from URL</button>
              </span>
              <div class="form-hint">Default avatar for member when they do not have an avatar. If you do not select any image, it will be generated using your logo or the first character of member name.</div>
            </div>

            <div class="mb-0">
              <label class="form-check">
                <input type="hidden" name="member_show_terms_policy_checkbox" value="0">
                <input type="checkbox" name="member_show_terms_policy_checkbox" value="1" class="form-check-input" @checked(old('member_show_terms_policy_checkbox', $settings['member_show_terms_policy_checkbox']))>
                <span class="form-check-label">Show Terms and Policy checkbox?</span>
              </label>
              <div class="form-hint ms-4">When it is enabled, users will need to agree to your Terms and Privacy Policy before they can register an account.</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </form>

  <div class="row mb-5 d-block d-md-flex">
    <div class="col-12 col-md-3"></div>
    <div class="col-12 col-md-9">
      <button type="submit" class="btn btn-primary btn-lg" form="member-settings-form">
        @include('core/base::admin.partials.icon', ['name' => 'save'])
        <span class="ms-2">Save settings</span>
      </button>
    </div>
  </div>

  @can('media.index')
    <x-media-picker
      name="member_default_avatar"
      url-name="member_default_avatar_url"
      :value="old('member_default_avatar', $settings['member_default_avatar'])"
      :url-value="$avatarUrl"
      modal-title="Media gallery"
      :inline="false"
      form-id="member-settings-form"
    />
  @endcan

  <x-admin-modal id="member-avatar-url-modal" title="Add from URL" size="md">
    <div class="mb-3">
      <label class="form-label required" for="member-avatar-url-modal-input">URL</label>
      <input type="url" id="member-avatar-url-modal-input" class="form-control" maxlength="2048" placeholder="https://" value="{{ $avatarUrl }}" data-member-avatar-url-modal-input>
    </div>

    <x-slot:footer>
      <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cancel</button>
      <button type="button" class="btn btn-primary" data-member-avatar-url-save>Save</button>
    </x-slot:footer>
  </x-admin-modal>
@endsection

@push('scripts')
  <script>
    ;(function () {
      var avatarPreview = document.querySelector('[data-member-avatar-preview]')
      var avatarEmptyPreview = document.querySelector('[data-member-avatar-empty-preview]')
      var avatarIdInput = document.getElementById('member-default-avatar')
      var avatarUrlInput = document.getElementById('member-default-avatar-url')
      var avatarUrlModal = document.getElementById('member-avatar-url-modal')
      var avatarUrlModalInput = document.querySelector('[data-member-avatar-url-modal-input]')
      var avatarUrlSave = document.querySelector('[data-member-avatar-url-save]')

      function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, function (character) {
          return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[character]
        })
      }

      function updateAvatarPreview(url) {
        if (!avatarPreview) {
          return
        }

        if (!url) {
          avatarPreview.innerHTML = avatarEmptyPreview ? avatarEmptyPreview.innerHTML : ''

          return
        }

        avatarPreview.innerHTML = '<div class="avatar avatar-xl rounded" style="background-image: url(' + escapeHtml(url) + ')"></div>'
      }

      document.addEventListener('admin:media-picker-selected', function (event) {
        if (!event.target || event.target.id !== 'member-default-avatar-media-picker') {
          return
        }

        var file = event.detail || {}

        if (avatarIdInput) {
          avatarIdInput.value = file.id || ''
        }

        if (avatarUrlInput) {
          avatarUrlInput.value = file.url || ''
        }

        updateAvatarPreview(file.thumbnail || file.url || '')
      })

      avatarUrlSave && avatarUrlSave.addEventListener('click', function () {
        var url = avatarUrlModalInput ? avatarUrlModalInput.value.trim() : ''

        if (!url || !avatarUrlModalInput.checkValidity()) {
          avatarUrlModalInput && avatarUrlModalInput.reportValidity()

          return
        }

        if (avatarIdInput) {
          avatarIdInput.value = ''
        }

        if (avatarUrlInput) {
          avatarUrlInput.value = url
        }

        updateAvatarPreview(url)

        var bootstrap = (window.tabler && window.tabler.bootstrap) || window.bootstrap

        if (bootstrap && avatarUrlModal) {
          bootstrap.Modal.getOrCreateInstance(avatarUrlModal).hide()
        }
      })
    })()
  </script>
@endpush
