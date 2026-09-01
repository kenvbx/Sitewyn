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

    // Display-name fallback for registry entries that only ship a key: map the
    // trailing action to a human label, otherwise headline the action word.
    $permissionActionLabels = [
        'index' => 'View list',
        'create' => 'Create',
        'store' => 'Create',
        'edit' => 'Edit',
        'update' => 'Update',
        'delete' => 'Delete',
        'destroy' => 'Delete',
        'manage' => 'Manage',
        'show' => 'View',
        'upload' => 'Upload',
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

      {{-- Each module renders as one full-width Tabler card: header holds the
           module master checkbox, green badge, permission count, and the
           collapse chevron; the body is a 2-column grid of feature groups.
           Permission rows are single uniform lines — the key and description
           live in the title tooltip instead of cluttering the row. --}}
      <div class="card-body">
        @error('permissions')
          <x-admin-alert type="danger">{{ $message }}</x-admin-alert>
        @enderror

        @foreach ($permissionTree as $module => $groups)
          @php
              $moduleLabel = $moduleLabels[$module] ?? \Illuminate\Support\Str::headline(\Illuminate\Support\Str::after($module, '/'));
          @endphp
          <div class="card mb-3" data-module-card>
            <div class="card-header d-flex align-items-center gap-2">
              <label class="form-check form-check-single mb-0">
                <input type="checkbox" class="form-check-input" data-module-master aria-label="Select all {{ $moduleLabel }} permissions" />
              </label>
              <span class="badge bg-green-lt">{{ $moduleLabel }}</span>
              <span class="text-secondary small">{{ $groups->sum(fn ($groupPermissions) => $groupPermissions->count()) }} permissions</span>
              <button type="button" class="btn btn-sm btn-ghost-secondary px-1 ms-auto" data-module-collapse aria-label="Toggle {{ $moduleLabel }} permissions">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
                  <path d="M6 9l6 6l6 -6" />
                </svg>
              </button>
            </div>

            <div class="card-body" data-module-body>
              <div class="row g-3">
                @foreach ($groups as $group => $groupPermissions)
                  @php
                      $groupLabel = \Illuminate\Support\Str::headline($group);
                  @endphp
                  <div class="col-md-6" data-group-block>
                    <div class="d-flex align-items-center gap-2 mb-2">
                      <label class="form-check form-check-single mb-0">
                        <input type="checkbox" class="form-check-input" data-group-master aria-label="Select all {{ $groupLabel }} permissions" />
                      </label>
                      <span class="fw-bold">{{ $groupLabel }}</span>
                      <span class="text-secondary small">{{ $groupPermissions->count() }}</span>
                    </div>

                    @foreach ($groupPermissions as $permission)
                      @php
                          $action = \Illuminate\Support\Str::afterLast($permission->key, '.');
                          $permissionName = $permission->name ?: ($permissionActionLabels[$action] ?? \Illuminate\Support\Str::headline($action));
                      @endphp
                      <label class="form-check d-flex align-items-center gap-2 mb-1" title="{{ $permission->key }}{{ $permission->description ? ' — ' . $permission->description : '' }}" data-perm-item>
                        <input type="checkbox" class="form-check-input m-0" name="permissions[]" value="{{ $permission->key }}" data-role-permission @checked(in_array($permission->key, old('permissions', $selectedPermissions), true)) />
                        <span>{{ $permissionName }}</span>
                      </label>
                    @endforeach
                  </div>
                @endforeach
              </div>
            </div>
          </div>
        @endforeach
      </div>

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
        // "All Permissions" shortcut, module collapse, Collapse/Expand all.
        // Scopes: a module is [data-module-card], a feature group is the
        // [data-group-block] inside it, permission rows keep [data-role-permission].
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
          var master = group.querySelector('[data-group-master]')

          if (master) refreshMaster(master, permissionBoxes(group))
        }

        function refreshModuleMaster(module) {
          var master = module.querySelector('[data-module-master]')

          if (master) refreshMaster(master, permissionBoxes(module))
          refreshAllMaster()
        }

        function refreshAllMaster() {
          var master = root.querySelector('[data-role-all-master]')

          if (master) refreshMaster(master, permissionBoxes(root))
        }

        function refreshAllMasters() {
          root.querySelectorAll('[data-group-block]').forEach(refreshGroupMaster)
          root.querySelectorAll('[data-module-card]').forEach(refreshModuleMaster)
          refreshAllMaster()
        }

        function setModuleCollapsed(card, collapsed) {
          var body = card.querySelector('[data-module-body]')
          var toggle = card.querySelector('[data-module-collapse]')

          if (body) body.classList.toggle('d-none', collapsed)
          if (toggle) toggle.classList.toggle('collapsed', collapsed)
        }

        root.addEventListener('change', function (event) {
          var target = event.target

          if (target.matches('[data-role-permission]')) {
            var group = target.closest('[data-group-block]')
            var module = target.closest('[data-module-card]')

            if (group) refreshGroupMaster(group)
            if (module) refreshModuleMaster(module)
            refreshAllMaster()
          } else if (target.matches('[data-group-master]')) {
            var group = target.closest('[data-group-block]')

            permissionBoxes(group).forEach(function (box) { box.checked = target.checked })
            refreshGroupMaster(group)
            refreshModuleMaster(group.closest('[data-module-card]'))
          } else if (target.matches('[data-module-master]')) {
            var module = target.closest('[data-module-card]')

            permissionBoxes(module).forEach(function (box) { box.checked = target.checked })
            module.querySelectorAll('[data-group-master]').forEach(function (master) {
              master.checked = target.checked
              master.indeterminate = false
            })
            refreshModuleMaster(module)
          } else if (target.matches('[data-role-all-master]')) {
            permissionBoxes(root).forEach(function (box) { box.checked = target.checked })
            root.querySelectorAll('[data-group-master]').forEach(function (master) {
              master.checked = target.checked
              master.indeterminate = false
            })
            root.querySelectorAll('[data-module-master]').forEach(function (master) {
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

        root.querySelectorAll('[data-module-collapse]').forEach(function (toggle) {
          toggle.addEventListener('click', function () {
            var card = toggle.closest('[data-module-card]')
            var body = card ? card.querySelector('[data-module-body]') : null

            if (card && body) {
              setModuleCollapsed(card, ! body.classList.contains('d-none'))
            }
          })
        })

        var collapseAll = root.querySelector('[data-role-collapse-all]')
        var expandAll = root.querySelector('[data-role-expand-all]')

        if (collapseAll) {
          collapseAll.addEventListener('click', function () {
            root.querySelectorAll('[data-module-card]').forEach(function (card) {
              setModuleCollapsed(card, true)
            })
          })
        }

        if (expandAll) {
          expandAll.addEventListener('click', function () {
            root.querySelectorAll('[data-module-card]').forEach(function (card) {
              setModuleCollapsed(card, false)
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
      [data-module-collapse] svg { transition: transform 0.15s ease; }
      [data-module-collapse].collapsed svg { transform: rotate(-90deg); }
    </style>
  @endpush
@endonce
