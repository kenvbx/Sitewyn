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
          <h3 class="card-title mb-0">Permission Flags</h3>
          <label class="form-check ms-auto">
            <input type="checkbox" id="expandCollapseAllTree" class="label label-default allTree form-check-input">
            <span class="form-check-label">All Permissions</span>
          </label>
        </div>
      </x-slot:header>
      <div class="card-body">
        @error('permissions')
          <x-admin-alert type="danger">{{ $message }}</x-admin-alert>
        @enderror

        {{-- Permission flags tree cloned 1:1 from Botble's
             core/acl::roles.permissions view: same structure, classes and
             ids (list-feature, #auto-checkboxes, #mainNode, li.collapsed,
             nested badges primary/yellow/cyan/lime/purple). Data-only
             differences: flags come from the Sitewyn permission registry
             (keys split on dots into path segments), real node checkboxes
             submit the original key as permissions[] (Botble submits
             flags[]), and grouping nodes have no name/value because their
             dot paths are not submittable permissions. --}}
        {{-- Permission flags tree cloned 1:1 from Botble's
             core/acl::roles.permissions view: same structure, classes and
             ids (list-feature, #auto-checkboxes, li.collapsed, nested badges
             primary/yellow/cyan/lime/purple). The master checkbox
             (#expandCollapseAllTree) now sits in the card header per the
             project owner's instruction. Data-only differences: flags come
             from the Sitewyn permission registry (keys split on dots into
             path segments), real node checkboxes submit the original key as
             permissions[] (the reference submits flags[]), and grouping
             nodes have no name/value because their dot paths are not
             submittable permissions. --}}
        <ul class="list-unstyled list-feature" id="auto-checkboxes" data-name="foo">
              @foreach ($children['root'] as $elementKey => $element)
                <li class="collapsed mx-0" style="background-color: inherit" id="node{{ $elementKey }}">
                  <label class="form-check">
                    @if ($flags[$element]['permission'])
                      <input type="checkbox" id="checkSelect{{ $elementKey }}" name="permissions[]" class="form-check-input" value="{{ $flags[$element]['flag'] }}" @checked(in_array($flags[$element]['flag'], old('permissions', $active), true))>
                    @else
                      <input type="checkbox" id="checkSelect{{ $elementKey }}" class="form-check-input">
                    @endif
                    <span class="form-check-label">
                      <span class="badge bg-primary-lt">{{ $flags[$element]['name'] }}</span>
                    </span>
                  </label>
                  @if (isset($children[$element]))
                    <ul class="list-unstyled">
                      @foreach ($children[$element] as $subKey => $subElements)
                        <li class="collapsed mx-0" style="background-color: inherit" id="node_sub_{{ $elementKey }}_{{ $subKey }}">
                          <label class="form-check">
                            @if ($flags[$subElements]['permission'])
                              <input type="checkbox" id="checkSelect_sub_{{ $elementKey }}_{{ $subKey }}" name="permissions[]" class="form-check-input" value="{{ $flags[$subElements]['flag'] }}" @checked(in_array($flags[$subElements]['flag'], old('permissions', $active), true))>
                            @else
                              <input type="checkbox" id="checkSelect_sub_{{ $elementKey }}_{{ $subKey }}" class="form-check-input">
                            @endif
                            <span class="form-check-label">
                              <span class="badge bg-yellow-lt">{{ $flags[$subElements]['name'] }}</span>
                            </span>
                          </label>
                          @if (isset($children[$subElements]))
                            <ul class="list-unstyled">
                              @foreach ($children[$subElements] as $subSubKey => $subSubElements)
                                <li class="collapsed mx-0" style="background-color: inherit" id="node_sub_sub_{{ $subSubKey }}">
                                  <label class="form-check">
                                    @if ($flags[$subSubElements]['permission'])
                                      <input type="checkbox" id="checkSelect_sub_sub{{ $subSubKey }}" name="permissions[]" class="form-check-input" value="{{ $flags[$subSubElements]['flag'] }}" @checked(in_array($flags[$subSubElements]['flag'], old('permissions', $active), true))>
                                    @else
                                      <input type="checkbox" id="checkSelect_sub_sub{{ $subSubKey }}" class="form-check-input">
                                    @endif
                                    <span class="form-check-label">
                                      <span class="badge bg-cyan-lt">{{ $flags[$subSubElements]['name'] }}</span>
                                    </span>
                                  </label>
                                  @if (isset($children[$subSubElements]))
                                    <ul class="list-unstyled">
                                      @foreach ($children[$subSubElements] as $grandChildrenKey => $grandChildrenElements)
                                        <li class="collapsed mx-0" style="background-color: inherit" id="node_grand_child{{ $grandChildrenKey }}">
                                          <label class="form-check">
                                            @if ($flags[$grandChildrenElements]['permission'])
                                              <input type="checkbox" id="checkSelect_grand_child{{ $grandChildrenKey }}" name="permissions[]" class="form-check-input" value="{{ $flags[$grandChildrenElements]['flag'] }}" @checked(in_array($flags[$grandChildrenElements]['flag'], old('permissions', $active), true))>
                                            @else
                                              <input type="checkbox" id="checkSelect_grand_child{{ $grandChildrenKey }}" class="form-check-input">
                                            @endif
                                            <span class="form-check-label">
                                              <span class="badge bg-lime-lt">{{ $flags[$grandChildrenElements]['name'] }}</span>
                                            </span>
                                          </label>
                                          @if (isset($children[$grandChildrenElements]))
                                            <ul class="list-unstyled">
                                              @foreach ($children[$grandChildrenElements] as $grandChildrenKeySub => $greatGrandChildrenElements)
                                                <li class="collapsed mx-0" style="background-color: inherit" id="node{{ $grandChildrenKey }}">
                                                  <label class="form-check">
                                                    @if ($flags[$grandChildrenElements]['permission'])
                                                      <input type="checkbox" id="checkSelect_grand_child{{ $grandChildrenKeySub }}" name="permissions[]" class="form-check-input" value="{{ $flags[$grandChildrenElements]['flag'] }}" @checked(in_array($flags[$grandChildrenElements]['flag'], old('permissions', $active), true))>
                                                    @else
                                                      <input type="checkbox" id="checkSelect_grand_child{{ $grandChildrenKeySub }}" class="form-check-input">
                                                    @endif
                                                    <span class="form-check-label">
                                                      <span class="badge bg-purple-lt">{{ $flags[$grandChildrenElements]['name'] }}</span>
                                                    </span>
                                                  </label>
                                              @endforeach
                                            </ul>
                                          @endif
                                        </li>
                                      @endforeach
                                    </ul>
                                  @endif
                                </li>
                              @endforeach
                            </ul>
                          @endif
                        </li>
                      @endforeach
                    </ul>
                  @endif
                </li>
              @endforeach
        </ul>
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
    {{-- Botble's RoleForm loads jquery-ui + jqueryTree styles and scripts
         for this screen; the local copies live under
         public/vendor/core-base/libraries (same files Botble publishes). --}}
    <link href="{{ asset('vendor/core-base/libraries/jquery-ui/jquery-ui.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('vendor/core-base/libraries/jquery-tree/jquery.tree.min.css') }}" rel="stylesheet" />

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
    <script src="{{ asset('vendor/core-base/libraries/jquery-ui/jquery-ui.min.js') }}"></script>
    <script src="{{ asset('vendor/core-base/libraries/jquery-tree/jquery.tree.min.js') }}"></script>
    <script>
      // Behavior cloned 1:1 from Botble core/acl resources/js/role.js (the
      // .checker binding is legacy there and matches markup that no longer
      // exists — kept verbatim like Botble ships it).
      class Role {
        init() {
          $('#auto-checkboxes li').tree({
            onCheck: {
              node: 'expand',
            },
            onUncheck: {
              node: 'expand',
            },
            dnd: false,
            selectable: false,
          })

          $('#mainNode .checker').change((event) => {
            let _self = $(event.currentTarget)
            let set = _self.attr('data-set')
            let checked = _self.is(':checked')
            $(set).each((index, el) => {
              if (checked) {
                $(el).attr('checked', true)
              } else {
                $(el).attr('checked', false)
              }
            })
          })
        }
      }

      $(() => {
        new Role().init()
      })
    </script>
    <style>
      /* Botble core/base sass partial _acl.scss rules for the permission
         flags tree (compiled form of the rules Botble ships in core.css).
         The --bb-* variables come from Botble's Tabler build; Sitewyn's
         Tabler does not define them, so the main node's left border simply
         falls back to no border. */
      .permissions-tree .daredevel-tree{border:none!important;border-left:var(--bb-border-width) solid var(--bb-border-color)!important;padding-top:5px}
      .permissions-tree .daredevel-tree>div{padding-left:10px}
      .permissions-tree .daredevel-tree:not(:has(ul))>.daredevel-tree-anchor{display:none}
      .permissions-tree .daredevel-tree-anchor{top:.5rem!important}
    </style>
  @endpush
@endonce
