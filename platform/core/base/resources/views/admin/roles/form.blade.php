@php
    // Shared create/edit role form (Botble-style "Permission Flags" tree).
    // Module badges are cosmetic labels for registry module slugs; the registry
    // data and permission keys themselves stay untouched.
    $moduleLabels = [
        'core/base' => 'Core',
        'package/page' => 'Pages',
        'package/blog' => 'Blog',
        'package/media' => 'Media',
    ];
@endphp

<div class="row row-cards">
  <div class="col-12">
    <x-admin-card title="Role information">
      <x-admin-form-group
        name="name"
        label="Name"
        :value="$role->name"
        required
        autocomplete="off"
        :maxlength="120"
        data-role-counter="120"
        invalid-feedback="Role name is required."
      />
      <x-admin-form-group
        name="description"
        label="Description"
        type="textarea"
        :value="$role->description"
        :rows="4"
        :maxlength="250"
        data-role-counter="250"
        invalid-feedback="Description may not be greater than 250 characters."
      />
      {{-- Deviation from the Botble sample: the slug input is kept so admins
           can still override the generated slug; leave it blank to auto-slug
           from the name. --}}
      <x-admin-form-group
        name="slug"
        label="Slug"
        :value="$role->slug"
        autocomplete="off"
        :maxlength="255"
        pattern="[A-Za-z0-9_-]+"
        hint="Leave blank to generate a slug from the name."
        invalid-feedback="Slug may only contain letters, numbers, dashes, and underscores."
      />
      {{-- Deviation from the Botble sample: the "Is admin" / "Is default"
           toggles are omitted — Sitewyn has no matching role columns and the
           super admin is a user flag (users.is_super_admin). --}}
    </x-admin-card>
  </div>

  <div class="col-12">
    <x-admin-card title="Permission Flags" data-role-permissions-tree>
      <x-slot:actions>
        <label class="form-check form-check-single mb-0">
          <input type="checkbox" class="form-check-input" data-role-all-master aria-label="Select all permissions" />
        </label>
        <button type="button" class="btn btn-sm" data-role-all-permissions>All Permissions</button>
        <button type="button" class="btn btn-sm btn-link" data-role-collapse-all>Collapse all</button>
        <button type="button" class="btn btn-sm btn-link" data-role-expand-all>Expand all</button>
      </x-slot:actions>

      @error('permissions')
        <x-admin-alert type="danger">{{ $message }}</x-admin-alert>
      @enderror

      @foreach ($permissionTree as $module => $groups)
        @php
            $moduleLabel = $moduleLabels[$module] ?? \Illuminate\Support\Str::headline(\Illuminate\Support\Str::after($module, '/'));
        @endphp
        <section class="mb-4" data-role-module>
          <div class="d-flex align-items-center gap-2 mb-2">
            <button type="button" class="btn btn-sm btn-ghost-secondary px-1" data-role-tree-toggle aria-label="Toggle {{ $moduleLabel }} permissions">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
                <path d="M6 9l6 6l6 -6" />
              </svg>
            </button>
            <label class="form-check form-check-single mb-0">
              <input type="checkbox" class="form-check-input" data-role-module-master aria-label="Select all {{ $moduleLabel }} permissions" />
            </label>
            <span class="badge bg-green-lt">{{ $moduleLabel }}</span>
            <span class="text-secondary small">{{ $groups->sum(fn ($groupPermissions) => $groupPermissions->count()) }} permissions</span>
          </div>

          <div class="row" data-role-tree-body>
            @foreach ($groups as $group => $groupPermissions)
              @php
                  $groupLabel = \Illuminate\Support\Str::headline($group);
              @endphp
              <div class="col-lg-4 py-2" data-role-group>
                <div class="d-flex align-items-center gap-1 mb-1">
                  <button type="button" class="btn btn-sm btn-ghost-secondary px-1" data-role-tree-toggle aria-label="Toggle {{ $groupLabel }} permissions">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
                      <path d="M6 9l6 6l6 -6" />
                    </svg>
                  </button>
                  <label class="form-check form-check-single mb-0">
                    <input type="checkbox" class="form-check-input" data-role-group-master aria-label="Select all {{ $groupLabel }} permissions" />
                  </label>
                  <span class="badge bg-orange-lt">{{ $groupLabel }}</span>
                </div>

                <div class="ps-4" data-role-tree-body>
                  @foreach ($groupPermissions as $permission)
                    <label class="form-check">
                      <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->key }}" data-role-permission @checked(in_array($permission->key, old('permissions', $selectedPermissions), true)) />
                      <span class="form-check-label">
                        {{ $permission->name }}
                        <span class="form-hint d-block"><code>{{ $permission->key }}</code>{{ $permission->description ? ' - ' . $permission->description : '' }}</span>
                      </span>
                    </label>
                  @endforeach
                </div>
              </div>
            @endforeach
          </div>
        </section>
      @endforeach

      <x-slot:footer>
        <input type="hidden" name="save_and_close" value="0" data-role-save-close />
        <div class="btn-list justify-content-end">
          <a href="{{ route('admin.system.roles.index') }}" class="btn btn-outline-secondary">Cancel</a>
          <button type="submit" class="btn btn-outline-primary" data-role-save>Save</button>
          <button type="submit" class="btn btn-primary" data-role-save-close-btn>Save and close</button>
        </div>
      </x-slot:footer>
    </x-admin-card>
  </div>
</div>

@once
  @push('scripts')
    <script>
      ;(function () {
        // Character counters for Name/Description: show "N/limit" next to the
        // field label, turning red past the limit. Advisory only — maxlength
        // and the server rules (120/250) still cap the stored values.
        document.querySelectorAll('[data-role-counter]').forEach(function (field) {
          var limit = parseInt(field.getAttribute('data-role-counter'), 10)
          var group = field.closest('.mb-3')
          var label = group ? group.querySelector('.form-label') : null

          if (! group || ! limit) return

          var counter = document.createElement('span')

          counter.className = 'float-end text-secondary fw-normal'

          function update() {
            var length = Array.from(field.value).length

            counter.textContent = length + '/' + limit
            counter.classList.toggle('text-danger', length > limit)
          }

          if (label) {
            label.appendChild(counter)
          } else {
            group.appendChild(counter)
          }

          field.addEventListener('input', update)
          update()
        })

        // Permission flags tree: master checkboxes with indeterminate state,
        // "All Permissions" shortcut, collapse toggles, Collapse/Expand all.
        var root = document.querySelector('[data-role-permissions-tree]')

        if (! root) return

        function permissionBoxes(scope) {
          return Array.prototype.slice.call(scope.querySelectorAll('[data-role-permission]'))
        }

        function refreshMaster(master, boxes) {
          var checked = boxes.filter(function (box) { return box.checked }).length

          master.checked = boxes.length > 0 && checked === boxes.length
          master.indeterminate = checked > 0 && checked < boxes.length
        }

        function refreshGroupMaster(group) {
          var master = group.querySelector('[data-role-group-master]')

          if (master) refreshMaster(master, permissionBoxes(group))
        }

        function refreshModuleMaster(module) {
          var master = module.querySelector('[data-role-module-master]')

          if (master) refreshMaster(master, permissionBoxes(module))
          refreshAllMaster()
        }

        function refreshAllMaster() {
          var master = root.querySelector('[data-role-all-master]')

          if (master) refreshMaster(master, permissionBoxes(root))
        }

        function refreshAllMasters() {
          root.querySelectorAll('[data-role-group]').forEach(refreshGroupMaster)
          root.querySelectorAll('[data-role-module]').forEach(refreshModuleMaster)
          refreshAllMaster()
        }

        function setCollapsed(wrapper, collapsed) {
          var body = wrapper.querySelector('[data-role-tree-body]')
          var toggle = wrapper.querySelector('[data-role-tree-toggle]')

          if (body) body.classList.toggle('d-none', collapsed)
          if (toggle) toggle.classList.toggle('collapsed', collapsed)
        }

        root.addEventListener('change', function (event) {
          var target = event.target

          if (target.matches('[data-role-permission]')) {
            var group = target.closest('[data-role-group]')
            var module = target.closest('[data-role-module]')

            if (group) refreshGroupMaster(group)
            if (module) refreshModuleMaster(module)
            refreshAllMaster()
          } else if (target.matches('[data-role-group-master]')) {
            var group = target.closest('[data-role-group]')

            permissionBoxes(group).forEach(function (box) { box.checked = target.checked })
            refreshGroupMaster(group)
            refreshModuleMaster(group.closest('[data-role-module]'))
          } else if (target.matches('[data-role-module-master]')) {
            var module = target.closest('[data-role-module]')

            permissionBoxes(module).forEach(function (box) { box.checked = target.checked })
            module.querySelectorAll('[data-role-group-master]').forEach(function (master) {
              master.checked = target.checked
              master.indeterminate = false
            })
            refreshModuleMaster(module)
          } else if (target.matches('[data-role-all-master]')) {
            permissionBoxes(root).forEach(function (box) { box.checked = target.checked })
            root.querySelectorAll('[data-role-group-master]').forEach(function (master) {
              master.checked = target.checked
              master.indeterminate = false
            })
            root.querySelectorAll('[data-role-module-master]').forEach(function (master) {
              master.checked = target.checked
              master.indeterminate = false
            })
            refreshAllMaster()
          }
        })

        var allButton = root.querySelector('[data-role-all-permissions]')

        if (allButton) {
          allButton.addEventListener('click', function () {
            permissionBoxes(root).forEach(function (box) { box.checked = true })
            refreshAllMasters()
          })
        }

        root.querySelectorAll('[data-role-tree-toggle]').forEach(function (toggle) {
          toggle.addEventListener('click', function () {
            var wrapper = toggle.closest('[data-role-group], [data-role-module]')
            var body = wrapper ? wrapper.querySelector('[data-role-tree-body]') : null

            if (wrapper && body) {
              setCollapsed(wrapper, ! body.classList.contains('d-none'))
            }
          })
        })

        var collapseAll = root.querySelector('[data-role-collapse-all]')
        var expandAll = root.querySelector('[data-role-expand-all]')

        if (collapseAll) {
          collapseAll.addEventListener('click', function () {
            root.querySelectorAll('[data-role-group]').forEach(function (group) {
              setCollapsed(group, true)
            })
          })
        }

        if (expandAll) {
          expandAll.addEventListener('click', function () {
            root.querySelectorAll('[data-role-group]').forEach(function (group) {
              setCollapsed(group, false)
            })
          })
        }

        // Footer buttons: "Save and close" flips the hidden input the
        // controller reads to decide between the edit page and the index.
        var saveCloseField = document.querySelector('[data-role-save-close]')

        if (saveCloseField) {
          var saveButton = document.querySelector('[data-role-save]')
          var saveCloseButton = document.querySelector('[data-role-save-close-btn]')

          if (saveButton) {
            saveButton.addEventListener('click', function () {
              saveCloseField.value = '0'
            })
          }

          if (saveCloseButton) {
            saveCloseButton.addEventListener('click', function () {
              saveCloseField.value = '1'
            })
          }
        }

        refreshAllMasters()
      })()
    </script>
    <style>
      [data-role-tree-toggle] svg { transition: transform 0.15s ease; }
      [data-role-tree-toggle].collapsed svg { transform: rotate(-90deg); }
    </style>
  @endpush
@endonce
