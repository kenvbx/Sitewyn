<div class="row row-cards">
  <div class="col-lg-5">
    <x-admin-card title="Account information">
      <x-admin-form-group name="name" label="Name" :value="$user->name" required autocomplete="off" :maxlength="255" invalid-feedback="Name is required." />
      <x-admin-form-group name="username" label="Username" :value="$user->username" autocomplete="off" :maxlength="255" pattern="[A-Za-z0-9_-]+" invalid-feedback="Username may only contain letters, numbers, dashes, and underscores." />
      <x-admin-form-group name="email" label="Email" type="email" :value="$user->email" required autocomplete="off" :maxlength="255" invalid-feedback="A valid email address is required." />
      <x-admin-form-group name="password" label="Password" type="password" :required="! $user->exists" autocomplete="new-password" :minlength="8" invalid-feedback="Password must be at least 8 characters." :hint="$user->exists ? 'Leave blank to keep the current password.' : null" />
      <x-admin-form-group name="password_confirmation" label="Confirm password" type="password" :required="! $user->exists" autocomplete="new-password" :minlength="8" invalid-feedback="Password confirmation does not match." data-admin-confirm="password" data-admin-confirm-message="Password confirmation does not match." />

      <x-slot:footer>
        <div class="text-end">
        <button type="submit" class="btn btn-primary">Save user</button>
        </div>
      </x-slot:footer>
    </x-admin-card>
  </div>
  <div class="col-lg-7">
    <x-admin-card title="Account status" class="mb-3">
      <label class="form-check form-switch">
        <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $user->exists ? $user->is_active : true)) @disabled($user->exists && auth('admin')->id() === $user->id) />
        <span class="form-check-label">Active account</span>
      </label>
      @if ($user->exists && auth('admin')->id() === $user->id)
        <input type="hidden" name="is_active" value="1" />
        <div class="form-hint">You cannot lock your own account.</div>
      @endif
      <label class="form-check form-switch mt-3">
        <input class="form-check-input" type="checkbox" name="is_super_admin" value="1" @checked((bool) old('is_super_admin', $user->is_super_admin)) />
        <span class="form-check-label">Super Admin</span>
      </label>
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
