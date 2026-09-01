<div class="row row-cards">
  <div class="col-12">
    <x-admin-card >
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
    <x-admin-card>
      <x-slot:header>
        <div class="d-flex align-items-center w-100">
          <h4 class="card-title">Permission Flags</h4>
          {{-- The master stays on the left next to the title; the
               collapse/expand links take over the ms-auto slot on the
               right and are wired by wireCollapseAll() in the @once
               script below. --}}
          <label class="ms-3 form-check">
            <input type="checkbox" id="expandCollapseAllTree" class="form-check-input label label-default allTree">
            <span class="form-check-label">All Permissions</span>
          </label>
          <div class="ms-auto d-flex align-items-center gap-2">
            <a href="#" id="collapseAllTree" class="link-secondary text-decoration-none">Collapse all</a>
            <span class="text-secondary">|</span>
            <a href="#" id="expandAllTree" class="link-secondary text-decoration-none">Expand all</a>
          </div>
        </div>
      </x-slot:header>
      <div class="card-body">
        @error('permissions')
          <x-admin-alert type="danger">{{ $message }}</x-admin-alert>
        @enderror

        {{-- Permission flags tree cloned 1:1 from the rendered ACL roles
             screen of the current Botble (div.permissions-tree with
             ul.parent_tree module cards — data-name="foo" ships verbatim
             in Botble's markup): each card is li.permissions-item with a
             div.permissions-header and a ul.row.permissions-body of
             feature groups. The hitarea divs, expandable/collapsable
             classes, display:none on nested lists and the +/- sprites are
             added at runtime by the classic jquery-treeview plugin — the
             same plugin Botble binds in its acl role.js — exactly like on
             the reference site, so the blade renders the plain lists the
             plugin expects. Data-only differences: the module cards,
             features and leaves come from the Sitewyn permission
             registry; module and grouping checkboxes have no name (their
             paths are not submittable permissions) while real permission
             checkboxes submit the original key as permissions[]. --}}
        <div class="permissions-tree" id="checkboxes-permisstions" data-name="foo">
          @foreach ($modules as $moduleIndex => $module)
            <ul class="parent_tree m-0 p-0 list-unstyled" id="node{{ $moduleIndex }}">
              <li class="permissions-item list-unstyled">
                <div class="permissions-header">
                  <label class="form-check">
                    <input type="checkbox" id="checkbox_one_{{ $moduleIndex }}" class="form-check-input check-success">
                    <span class="form-check-label">
                      <span class="badge bg-success-lt">{{ $module['name'] }}</span>
                    </span>
                  </label>
                </div>
                <ul class="row permissions-body has-children">
                  @foreach ($module['features'] as $featureIndex => $feature)
                    @if (isset($feature['leaf']))
                      {{-- Single-action feature (Settings/Plugins/Backups/
                           Menus/Widgets): the feature li holds the leaf
                           directly — no hitarea, no badge. --}}
                      <li class="list-unstyled col-4 m-0" style="background-color: inherit" id="node_sub_{{ $moduleIndex }}_{{ $featureIndex }}">
                        <label class="form-check">
                          <input type="checkbox" id="checkbox_two_{{ $moduleIndex }}_{{ $featureIndex }}" name="permissions[]" class="form-check-input" value="{{ $feature['leaf']['key'] }}" @checked(in_array($feature['leaf']['key'], old('permissions', $active), true))>
                          <span class="form-check-label">{{ $feature['leaf']['text'] }}</span>
                        </label>
                      </li>
                    @elseif (empty($feature['children']))
                      {{-- Real-permission feature without leaves
                           (Permissions/Audit): flat li, badge stays. --}}
                      <li class="list-unstyled col-4 m-0" style="background-color: inherit" id="node_sub_{{ $moduleIndex }}_{{ $featureIndex }}">
                        <label class="form-check">
                          <input type="checkbox" id="checkbox_two_{{ $moduleIndex }}_{{ $featureIndex }}" name="permissions[]" class="form-check-input" value="{{ $feature['permission'] }}" @checked(in_array($feature['permission'], old('permissions', $active), true))>
                          <span class="form-check-label">
                            <span class="badge bg-primary-lt">{{ $feature['name'] }}</span>
                          </span>
                        </label>
                      </li>
                    @else
                      <li class="list-unstyled col-4 m-0" style="background-color: inherit" id="node_sub_{{ $moduleIndex }}_{{ $featureIndex }}">
                        <label class="form-check">
                          @if ($feature['permission'])
                            <input type="checkbox" id="checkbox_two_{{ $moduleIndex }}_{{ $featureIndex }}" name="permissions[]" class="form-check-input" value="{{ $feature['permission'] }}" @checked(in_array($feature['permission'], old('permissions', $active), true))>
                          @else
                            <input type="checkbox" id="checkbox_two_{{ $moduleIndex }}_{{ $featureIndex }}" class="form-check-input">
                          @endif
                          <span class="form-check-label">
                            <span class="badge bg-primary-lt">{{ $feature['name'] }}</span>
                          </span>
                        </label>
                        <ul class="list-unstyled">
                          @foreach ($feature['children'] as $subIndex => $child)
                            @if (isset($child['name']))
                              {{-- Sub level (only Core → System Users):
                                   yellow badge; the grouping checkbox
                                   submits nothing. --}}
                              <li style="background-color: inherit" id="node_sub_sub_{{ $subIndex }}">
                                <label class="form-check">
                                  <input type="checkbox" id="checkbox_three_{{ $subIndex }}" class="form-check-input check-yellow">
                                  <span class="form-check-label">
                                    <span class="badge bg-yellow-lt">{{ $child['name'] }}</span>
                                  </span>
                                </label>
                                <ul class="list-unstyled">
                                  @foreach ($child['children'] as $leafIndex => $leaf)
                                    <li style="background-color: inherit" id="node_grand_child{{ $leafIndex }}">
                                      <label class="form-check">
                                        <input type="checkbox" id="checkbox_four_{{ $leafIndex }}" name="permissions[]" class="form-check-input" value="{{ $leaf['key'] }}" @checked(in_array($leaf['key'], old('permissions', $active), true))>
                                        <span class="form-check-label">{{ $leaf['text'] }}</span>
                                      </label>
                                    </li>
                                  @endforeach
                                </ul>
                              </li>
                            @else
                              <li style="background-color: inherit" id="node_sub_sub_{{ $subIndex }}">
                                <label class="form-check">
                                  <input type="checkbox" id="checkbox_three_{{ $subIndex }}" name="permissions[]" class="form-check-input" value="{{ $child['key'] }}" @checked(in_array($child['key'], old('permissions', $active), true))>
                                  <span class="form-check-label">{{ $child['text'] }}</span>
                                </label>
                              </li>
                            @endif
                          @endforeach
                        </ul>
                      </li>
                    @endif
                  @endforeach
                </ul>
              </li>
            </ul>
          @endforeach
        </div>
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
    {{-- Botble's rendered ACL roles screen loads the classic
         jquery-treeview plugin (registered for this tree in Botble's
         platform/core/base/config/assets.php) plus jQuery — the local
         copies live under public/vendor/core-base/libraries (same files
         Botble publishes). The libraries the previous markup needed are
         gone with it. --}}
    <link href="{{ asset('vendor/core-base/libraries/jquery-treeview/jquery.treeview.min.css') }}" rel="stylesheet" />

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
      })()
    </script>

    <script src="{{ asset('vendor/core-base/libraries/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/core-base/libraries/jquery-treeview/jquery.treeview.min.js') }}"></script>
    <script>
      // Tree init + checkbox cascade cloned 1:1 from Botble core/acl
      // resources/js/role.js: the plugin turns every .has-children list
      // into the hitarea/expandable tree (all levels collapsed by
      // default, "medium" height animation, +/- sprites from the plugin
      // CSS), and clicking any checkbox checks its descendants and syncs
      // the parent group checkboxes (a fully-checked group checks its
      // parent, a partially-checked one clears it).
      class Role {
        init() {
          let $checkboxes = $('.has-children')
          if ($checkboxes.length) {
            $checkboxes.map((index, value) => {
              $(value).treeview({
                collapsed: true,
                animated: 'medium',
                control: '#sidetreecontrol',
                persist: 'location',
              })
            })
          }

          $('#checkboxes-permisstions :checkbox').on('click', function (event) {
            event.stopPropagation()
            let _self = $(event.currentTarget)
            let checked = _self.is(':checked'),
              parent_li = _self.closest('li'),
              parent_uls = parent_li.parents('ul')
            parent_li.find(':checkbox').prop('checked', checked)
            parent_uls.each(function () {
              let parent_ul = $(this),
                parent_state = parent_ul.find(':checkbox').length == parent_ul.find(':checked').length
              parent_ul.siblings(':checkbox').prop('checked', parent_state)
            })
          })

          this.wireAllPermissionsMaster()
          this.wireCollapseAll()
        }

        // DEVIATION from Botble (its #allTreeChecked master is a flat
        // check-all without indeterminate state, and the card-header
        // master is a Sitewyn placement per the project owner's
        // instruction): the header "All Permissions" master sets every
        // checkbox in the tree — permission leaves (name="permissions[]")
        // and grouping nodes without a name alike — via prop() only,
        // without .click() triggers, so the cascade above is never
        // re-entered. The master then reflects the tree: checked when all
        // boxes are checked, indeterminate when some are, unchecked when
        // none are.
        wireAllPermissionsMaster() {
          const master = $('#expandCollapseAllTree')
          const tree = $('#checkboxes-permisstions')

          const syncMaster = () => {
            const boxes = tree.find('input[type="checkbox"]')
            const checked = boxes.filter(':checked').length

            master.prop('checked', boxes.length > 0 && checked === boxes.length)
            master.prop('indeterminate', checked > 0 && checked < boxes.length)
          }

          master.on('change', () => {
            tree.find('input[type="checkbox"]').prop('checked', master.is(':checked'))
            syncMaster()
          })

          tree.on('change', 'input[type="checkbox"]', syncMaster)

          syncMaster()
        }

        // Collapse/expand every branch of the tree at once. Verified in
        // jquery.treeview.min.js: the plugin's toggler swaps the
        // expandable/collapsable hitarea classes both ways (swapClass)
        // and height-toggles the direct ul, so one click per hitarea is
        // enough for the nested tree — all hitareas are captured before
        // any click fires, and a simulated hitarea click never re-enters
        // another hitarea's handler (a hitarea is a sibling of the
        // nested lists, never an ancestor of another hitarea).
        wireCollapseAll() {
          const tree = $('#checkboxes-permisstions')

          $('#collapseAllTree').on('click', (event) => {
            event.preventDefault()
            tree.find('.collapsable-hitarea').trigger('click')
          })

          $('#expandAllTree').on('click', (event) => {
            event.preventDefault()
            tree.find('.expandable-hitarea').trigger('click')
          })
        }
      }

      $(() => {
        new Role().init()
      })
    </script>
    <style>
      /* Botble's ACL permissions tree rules for the div.permissions-tree
         markup (the .permissions-tree block of the current Botble's
         core.css), copied verbatim in source order. The old markup's
         tree rules are gone with it, and the check-blue/danger/secondary
         variants (never rendered here) are left out — check-success and
         check-yellow are the two the tree uses. The --bb-bg-* custom
         properties the dark-mode rules consume are defined in Botble's
         core.css ":root,[data-bs-theme=light]" and "[data-bs-theme=dark]"
         blocks (resolved from --bb-white/--bb-gray-900/--bb-gray-800) —
         Sitewyn's Tabler build does not ship them, so those values are
         copied verbatim too. */
      [data-bs-theme=dark] .permissions-tree .permissions-item{background-color:var(--bb-bg-forms)}
      [data-bs-theme=dark] .permissions-tree .permissions-item .permissions-header{background-color:var(--bb-bg-surface);border-bottom:1px solid var(--bb-bg-surface)}
      .permissions-tree .permissions-item{background-color:#f6f8fb;border-radius:4px;margin-bottom:10px;padding:0}
      .permissions-tree .permissions-item .permissions-body,.permissions-tree .permissions-item .permissions-header{padding:10px}
      .permissions-tree .permissions-item .permissions-body{padding:10px 20px}
      .permissions-tree .permissions-item .permissions-header{background-color:#f2f5f7;border-bottom:1px solid #cfd7e0}
      .permissions-tree .single-node li{margin:0;padding:3px 0 3px 18px}
      .permissions-tree .form-check .form-check-input.check-success:checked{background-color:#198754}
      .permissions-tree .form-check .form-check-input.check-success:focus{border-color:#198754}
      .permissions-tree .form-check .form-check-input.check-yellow:checked{background-color:#efc656}
      .permissions-tree .form-check .form-check-input.check-yellow:focus{border-color:#efc656}
      :root, [data-bs-theme=light] { --bb-bg-forms: #fff; --bb-bg-surface: #fff; }
      [data-bs-theme=dark] { --bb-bg-forms: #111827; --bb-bg-surface: #1f2937; }
    </style>
  @endpush
@endonce
