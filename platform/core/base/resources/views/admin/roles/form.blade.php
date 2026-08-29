<div class="row row-cards">
  <div class="col-lg-5">
    <x-admin-card title="Role information">
      <x-admin-form-group name="name" label="Name" :value="$role->name" required autocomplete="off" :maxlength="255" invalid-feedback="Role name is required." />
      <x-admin-form-group name="slug" label="Slug" :value="$role->slug" autocomplete="off" :maxlength="255" pattern="[A-Za-z0-9_-]+" invalid-feedback="Slug may only contain letters, numbers, dashes, and underscores." />
      <x-admin-form-group name="description" label="Description" type="textarea" :value="$role->description" :rows="4" :maxlength="255" invalid-feedback="Description may not be greater than 255 characters." />

      <x-slot:footer>
        <div class="text-end">
        <button type="submit" class="btn btn-primary">Save role</button>
        </div>
      </x-slot:footer>
    </x-admin-card>
  </div>
  <div class="col-lg-7">
    <x-admin-card title="Permissions">
      @error('permissions')
        <x-admin-alert type="danger">{{ $message }}</x-admin-alert>
      @enderror
      @foreach ($permissionGroups as $group => $permissions)
        <div class="mb-4">
          <div class="subheader mb-2">{{ \Illuminate\Support\Str::headline($group) }}</div>
          <div class="divide-y">
            @foreach ($permissions as $permission)
              <label class="row">
                <span class="col">
                  <span class="form-label mb-0">{{ $permission->name }}</span>
                  <span class="form-hint"><code>{{ $permission->key }}</code>{{ $permission->description ? ' - ' . $permission->description : '' }}</span>
                </span>
                <span class="col-auto">
                  <label class="form-check form-check-single form-switch">
                    <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->key }}" @checked(in_array($permission->key, old('permissions', $selectedPermissions), true)) />
                  </label>
                </span>
              </label>
            @endforeach
          </div>
        </div>
      @endforeach
    </x-admin-card>
  </div>
</div>
