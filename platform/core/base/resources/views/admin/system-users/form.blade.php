@php
    // Create vs edit only changes the password requirement and the submit
    // label — field names and validation stay identical on both surfaces.
    $isCreate = ! $user->exists;
    $errorBag = $errors ?? new \Illuminate\Support\ViewErrorBag;

    // Self-edits additionally collect the current password when the password
    // changes; admins editing somebody else never see that field.
    $isSelf = $user->exists && auth('admin')->id() === $user->id;

    // Initials fallback for the avatar preview (same pattern as the header
    // avatar in the master layout): first letter of each name part, two max.
    $avatarInitials = collect(explode(' ', $user->name))
        ->filter()
        ->map(fn (string $part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
        ->take(2)
        ->implode('') ?: \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($user->email, 0, 1));
@endphp
<div class="row row-cards">
  {{-- Main column: Botble-style tabbed account card. The tabs only hide and
       show fields — one shared form still submits every pane, including the
       hidden ones (native constraint validation covers display:none inputs). --}}
  <div class="col-lg-7">
    <x-admin-card>
      <x-slot:header>
        <ul class="nav nav-tabs card-header-tabs" role="tablist">
          <li class="nav-item" role="presentation">
            <a href="#system-user-tab-profile" class="nav-link active" data-bs-toggle="tab" role="tab" aria-selected="true" aria-controls="system-user-tab-profile">User profile</a>
          </li>
          @if ($user->exists)
            <li class="nav-item" role="presentation">
              <a href="#system-user-tab-avatar" class="nav-link" data-bs-toggle="tab" role="tab" aria-selected="false" aria-controls="system-user-tab-avatar">Avatar</a>
            </li>
          @endif
          <li class="nav-item" role="presentation">
            <a href="#system-user-tab-password" class="nav-link" data-bs-toggle="tab" role="tab" aria-selected="false" aria-controls="system-user-tab-password">Change password</a>
          </li>
          <li class="nav-item" role="presentation">
            <a href="#system-user-tab-preferences" class="nav-link" data-bs-toggle="tab" role="tab" aria-selected="false" aria-controls="system-user-tab-preferences">Preferences</a>
          </li>
        </ul>
      </x-slot:header>

      <div class="tab-content" id="system-user-tab-content">
        <div class="tab-pane show active" id="system-user-tab-profile" role="tabpanel" aria-labelledby="system-user-tab-profile" tabindex="0">
          <x-admin-form-group name="name" label="Name" :value="$user->name" required autocomplete="off" :maxlength="255" invalid-feedback="Name is required." />

          <div class="row">
            <div class="col-md-6">
              <x-admin-form-group name="username" label="Username" :value="$user->username" autocomplete="off" :maxlength="255" pattern="[A-Za-z0-9_-]+" invalid-feedback="Username may only contain letters, numbers, dashes, and underscores." />
            </div>
            <div class="col-md-6">
              <x-admin-form-group name="email" label="Email" type="email" :value="$user->email" required autocomplete="off" :maxlength="255" invalid-feedback="A valid email address is required." />
            </div>
          </div>
        </div>

        @if ($user->exists)
          <div class="tab-pane" id="system-user-tab-avatar" role="tabpanel" aria-labelledby="system-user-tab-avatar" tabindex="0">
            {{-- The file input is part of the main form: the upload only
                 happens on submit, the buttons below just drive the local
                 preview and the remove flag. Avatar block follows the Tabler
                 settings.html pattern: the initials sit inside the span and a
                 stored avatar is a background-image on the same span. --}}
            <div class="mb-3">
              <div class="row align-items-center">
                <div class="col-auto">
                  <span class="avatar avatar-xl" data-avatar-preview @if ($user->avatar) style="background-image: url('{{ $user->avatar_url }}')" @endif>{{ $avatarInitials }}</span>
                </div>
                <div class="col-auto">
                  <button type="button" class="btn" data-avatar-choose>Change avatar</button>
                </div>
                <div class="col-auto">
                  <button type="button" class="btn btn-ghost-danger" data-avatar-remove @unless($user->avatar) hidden @endunless>Delete avatar</button>
                </div>
              </div>
              <div class="form-hint">JPG, PNG or WebP, up to 2&nbsp;MB.</div>
            </div>
            <input type="file" id="avatar" name="avatar" accept=".jpg,.jpeg,.png,.webp" class="d-none" data-avatar-input aria-label="Choose avatar image" />
            <input type="hidden" name="avatar_remove" value="0" data-avatar-remove-flag />
          </div>
        @endif

        <div class="tab-pane" id="system-user-tab-password" role="tabpanel" aria-labelledby="system-user-tab-password" tabindex="0">
          @if ($isSelf)
            {{-- Self-edit only: proving the current password when the
                 password changes. Admins editing somebody else never need it. --}}
            <div class="mb-3">
              <label class="form-label required" for="current_password">Current Password</label>
              <div class="input-group input-group-flat">
                <input type="password" id="current_password" name="current_password" value="{{ old('current_password') }}" autocomplete="current-password" class="form-control {{ $errorBag->has('current_password') ? 'is-invalid' : '' }}" />
                <span class="input-group-text">
                  <button type="button" class="link-secondary input-group-link border-0 bg-transparent p-0" title="Show password" aria-label="Show password" aria-pressed="false" data-admin-password-toggle="current_password">
                    <span class="admin-password-icon-show">@include('core/base::admin.partials.icon', ['name' => 'eye'])</span>
                    <span class="admin-password-icon-hide d-none">@include('core/base::admin.partials.icon', ['name' => 'eye-off'])</span>
                  </button>
                </span>
                <div class="invalid-feedback">The current password is incorrect.</div>
              </div>
              <div class="form-hint">Only required when you set a new password.</div>
            </div>
          @endif

          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label {{ $isCreate ? 'required' : '' }}" for="password">New password</label>
                <div class="input-group input-group-flat">
                  <input type="password" id="password" name="password" value="{{ old('password') }}" autocomplete="new-password" @required($isCreate) minlength="8" class="form-control {{ $errorBag->has('password') ? 'is-invalid' : '' }}" />
                  <span class="input-group-text">
                    <button type="button" class="link-secondary input-group-link border-0 bg-transparent p-0" title="Show password" aria-label="Show password" aria-pressed="false" data-admin-password-toggle="password">
                      <span class="admin-password-icon-show">@include('core/base::admin.partials.icon', ['name' => 'eye'])</span>
                      <span class="admin-password-icon-hide d-none">@include('core/base::admin.partials.icon', ['name' => 'eye-off'])</span>
                    </button>
                  </span>
                  <div class="invalid-feedback">Password must be at least 8 characters.</div>
                </div>
                @if (! $isCreate)
                  <div class="form-hint">Leave blank to keep the current password.</div>
                @endif
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label {{ $isCreate ? 'required' : '' }}" for="password_confirmation">Confirm password</label>
                <div class="input-group input-group-flat">
                  <input type="password" id="password_confirmation" name="password_confirmation" value="{{ old('password_confirmation') }}" autocomplete="new-password" @required($isCreate) minlength="8" class="form-control {{ $errorBag->has('password_confirmation') ? 'is-invalid' : '' }}" data-admin-confirm="password" data-admin-confirm-message="Password confirmation does not match." />
                  <span class="input-group-text">
                    <button type="button" class="link-secondary input-group-link border-0 bg-transparent p-0" title="Show password" aria-label="Show password" aria-pressed="false" data-admin-password-toggle="password_confirmation">
                      <span class="admin-password-icon-show">@include('core/base::admin.partials.icon', ['name' => 'eye'])</span>
                      <span class="admin-password-icon-hide d-none">@include('core/base::admin.partials.icon', ['name' => 'eye-off'])</span>
                    </button>
                  </span>
                  <div class="invalid-feedback">Password confirmation does not match.</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="tab-pane" id="system-user-tab-preferences" role="tabpanel" aria-labelledby="system-user-tab-preferences" tabindex="0">
          {{-- Display only: a single language is seeded, so the select stays
               disabled and submits nothing (disabled controls are not sent). --}}
          <div class="mb-3">
            <label class="form-label" for="admin-panel-language">Admin panel language</label>
            <select class="form-select" id="admin-panel-language" disabled>
              <option value="en" selected>English - en</option>
            </select>
            <div class="form-hint">More languages coming soon.</div>
          </div>

          <div class="mb-3">
            <div class="form-label">Theme mode</div>
            <div>
              <label class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="admin_theme" value="light" data-admin-theme-radio checked />
                <span class="form-check-label">Light</span>
              </label>
              <label class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="admin_theme" value="dark" data-admin-theme-radio />
                <span class="form-check-label">Dark</span>
              </label>
            </div>
            <div class="form-hint">Stored in this browser and kept in sync with the theme toggle in the top bar.</div>
          </div>
        </div>
      </div>

      <x-slot:footer>
        <div class="text-end">
          <button type="submit" class="btn btn-primary">
            @include('core/base::admin.partials.icon', ['name' => 'circle-check'])
            {{ $isCreate ? 'Save user' : 'Update' }}
          </button>
        </div>
      </x-slot:footer>
    </x-admin-card>
  </div>
  <div class="col-lg-5">
    <x-admin-card title="Account status" class="mb-3">
      <label class="form-check form-switch">
        <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $user->exists ? $user->is_active : true)) @disabled($user->exists && auth('admin')->id() === $user->id) />
        <span class="form-check-label">Active account</span>
      </label>
      @if ($user->exists && auth('admin')->id() === $user->id)
        <input type="hidden" name="is_active" value="1" />
        <div class="form-hint">You cannot lock your own account.</div>
      @endif
      @if (auth('admin')->user()->is_super_admin)
        <label class="form-check form-switch mt-3">
          <input class="form-check-input" type="checkbox" name="is_super_admin" value="1" @checked((bool) old('is_super_admin', $user->is_super_admin)) />
          <span class="form-check-label">Super Admin</span>
        </label>
      @endif
    </x-admin-card>
    <x-admin-card title="Roles">
      @error('roles')
        <x-admin-alert type="danger">{{ $message }}</x-admin-alert>
      @enderror
      <div class="divide-y">
        @forelse ($roles as $role)
          <label class="row">
            <span class="col">
              <span class="form-label mb-0">{{ $role->name }}</span>
              <span class="form-hint"><code>{{ $role->slug }}</code>{{ $role->description ? ' - ' . $role->description : '' }}</span>
            </span>
            <span class="col-auto">
              <label class="form-check form-check-single form-switch">
                <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role->id }}" @checked(in_array($role->id, old('roles', $selectedRoles), false)) />
              </label>
            </span>
          </label>
        @empty
          <div class="empty">
            <p class="empty-title">No roles found</p>
            <p class="empty-subtitle text-secondary">Create roles before assigning them to admin users.</p>
          </div>
        @endforelse
      </div>
    </x-admin-card>
  </div>
</div>

@once
  @push('scripts')
    <script>
      ;(function () {
        // --- Password show/hide (eye toggle, Tabler sign-in pattern) ---
        document.querySelectorAll('[data-admin-password-toggle]').forEach(function (button) {
          button.addEventListener('click', function () {
            var targetId = button.getAttribute('data-admin-password-toggle')
            var input = targetId ? document.getElementById(targetId) : button.closest('.input-group').querySelector('input')
            var show = input.type === 'password'
            var label = show ? 'Hide password' : 'Show password'

            input.type = show ? 'text' : 'password'
            button.querySelector('.admin-password-icon-show').classList.toggle('d-none', ! show)
            button.querySelector('.admin-password-icon-hide').classList.toggle('d-none', show)
            button.setAttribute('aria-pressed', show ? 'true' : 'false')
            button.setAttribute('title', label)
            button.setAttribute('aria-label', label)
          })
        })

        // --- Avatar tab: local preview + remove (upload happens on submit) ---
        var avatarInput = document.querySelector('[data-avatar-input]')

        if (avatarInput) {
          var preview = document.querySelector('[data-avatar-preview]')
          var chooseButton = document.querySelector('[data-avatar-choose]')
          var removeButton = document.querySelector('[data-avatar-remove]')
          var removeFlag = document.querySelector('[data-avatar-remove-flag]')
          var previewUrl = null

          // Tabler settings.html pattern: the initials live as the span's
          // text and a picture is a background-image on the same span, so
          // the fallback is just clearing the background again.
          var initials = preview ? preview.textContent.trim() : ''

          var showImagePreview = function (src) {
            if (! preview) {
              return
            }

            preview.style.backgroundImage = 'url("' + src + '")'
            preview.textContent = ''

            if (removeButton) {
              removeButton.hidden = false
            }
          }

          var showFallbackPreview = function () {
            if (! preview) {
              return
            }

            if (previewUrl) {
              URL.revokeObjectURL(previewUrl)
              previewUrl = null
            }

            preview.style.backgroundImage = ''
            preview.textContent = initials

            if (removeButton) {
              removeButton.hidden = true
            }
          }

          // Server-rendered state: a background image means preview mode —
          // the initials would draw over it, so drop the text. Otherwise the
          // markup already shows the fallback.
          if (preview && preview.style.backgroundImage) {
            preview.textContent = ''
          }

          if (chooseButton) {
            chooseButton.addEventListener('click', function () {
              avatarInput.click()
            })
          }

          avatarInput.addEventListener('change', function () {
            var file = avatarInput.files && avatarInput.files[0]

            if (! file) {
              return
            }

            if (previewUrl) {
              URL.revokeObjectURL(previewUrl)
            }

            previewUrl = URL.createObjectURL(file)
            showImagePreview(previewUrl)

            // A new upload always wins over the remove flag.
            if (removeFlag) {
              removeFlag.value = '0'
            }
          })

          if (removeButton) {
            removeButton.addEventListener('click', function () {
              avatarInput.value = ''
              showFallbackPreview()

              if (removeFlag) {
                removeFlag.value = '1'
              }
            })
          }
        }

        // --- Theme mode radios (Preferences tab) ---
        // Same mechanism as the top-bar toggle: localStorage 'sitewyn-admin-theme'
        // + html[data-bs-theme]. The top-bar moon/sun icons are pure CSS off that
        // attribute, so setting it here keeps the header toggle in sync without
        // extra code; the reverse direction arrives via the custom event below.
        var root = document.documentElement
        var themeRadios = document.querySelectorAll('[data-admin-theme-radio]')

        var currentTheme = function () {
          // tabler-theme.js strips data-bs-theme in default light — absent = light.
          return root.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light'
        }

        var syncThemeRadios = function (theme) {
          themeRadios.forEach(function (radio) {
            radio.checked = radio.value === theme
          })
        }

        if (themeRadios.length) {
          syncThemeRadios(currentTheme())

          themeRadios.forEach(function (radio) {
            radio.addEventListener('change', function () {
              if (! radio.checked) {
                return
              }

              root.setAttribute('data-bs-theme', radio.value)
              localStorage.setItem('sitewyn-admin-theme', radio.value)
            })
          })

          document.addEventListener('sitewyn-admin-theme-changed', function (event) {
            syncThemeRadios(event.detail && event.detail.theme ? event.detail.theme : currentTheme())
          })
        }

        // --- Surface the tab holding the first invalid field on submit ---
        // Hidden panes are still validated natively, but the error markup would
        // stay invisible (display:none) — jump to the offending tab instead.
        var tabContent = document.getElementById('system-user-tab-content')
        var form = tabContent ? tabContent.closest('form') : null

        if (form) {
          form.addEventListener('submit', function () {
            var invalid = form.querySelector(':invalid')

            if (! invalid) {
              return
            }

            var pane = invalid.closest('.tab-pane')

            if (! pane || pane.classList.contains('active')) {
              return
            }

            var trigger = document.querySelector('.nav-link[href="#' + pane.id + '"]')
            var bootstrap = (window.tabler && window.tabler.bootstrap) || window.bootstrap

            if (trigger && bootstrap && bootstrap.Tab) {
              bootstrap.Tab.getOrCreateInstance(trigger).show()
            }
          })
        }
      })()
    </script>
  @endpush
@endonce
