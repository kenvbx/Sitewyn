@extends('core/base::admin.layouts.master')

@section('title', 'Menu builder - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('pretitle', 'Content')

@section('page-title', 'Menu builder')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.menus.index') }}">Menus</a></li>
  <li class="breadcrumb-item active" aria-current="page">{{ $menu->name }}</li>
@endsection

@section('page-actions')
  <div class="btn-list">
    <a href="{{ route('admin.menus.edit', $menu) }}" class="btn">
      Menu settings
    </a>
  </div>
@endsection

@section('content')
  @php
      // Badge text + colour per item type, shared by the server-rendered
      // rows below (the JS re-render mirrors these).
      $typeMeta = [
          'page' => ['label' => 'Page', 'class' => 'bg-blue-lt'],
          'post' => ['label' => 'Post', 'class' => 'bg-purple-lt'],
          'custom' => ['label' => 'Link', 'class' => 'bg-secondary-lt'],
      ];
  @endphp

  @if ($errors->any())
    <x-admin-alert type="danger" title="The menu structure could not be saved — fix the rows below and save again.">
      <ul class="mb-0 ps-3">
        @foreach ($errors->all() as $message)
          <li>{{ $message }}</li>
        @endforeach
      </ul>
    </x-admin-alert>
  @endif

  <div class="row row-cards">
    <div class="col-lg-4">
      <x-admin-card title="Pages" subtitle="Published pages to link to.">
        <div data-menu-source="pages">
          <div class="menu-source-list">
            @forelse ($pages as $page)
              <label class="form-check">
                <input type="checkbox" class="form-check-input" value="{{ $page->id }}" data-title="{{ $page->title }}">
                <span class="form-check-label">{{ $page->title }}</span>
              </label>
            @empty
              <p class="text-secondary mb-0">No published pages yet.</p>
            @endforelse
          </div>
          <button type="button" class="btn btn-sm btn-primary mt-2" data-menu-add>Add selected pages</button>
        </div>
      </x-admin-card>

      <x-admin-card title="Posts" subtitle="Published posts to link to.">
        <div data-menu-source="posts">
          <div class="menu-source-list">
            @forelse ($posts as $post)
              <label class="form-check">
                <input type="checkbox" class="form-check-input" value="{{ $post->id }}" data-title="{{ $post->title }}">
                <span class="form-check-label">{{ $post->title }}</span>
              </label>
            @empty
              <p class="text-secondary mb-0">No published posts yet.</p>
            @endforelse
          </div>
          <button type="button" class="btn btn-sm btn-primary mt-2" data-menu-add>Add selected posts</button>
        </div>
      </x-admin-card>

      <x-admin-card title="Custom URL" subtitle="Any path or external link.">
        <div>
          <div class="mb-2">
            <label class="form-label" for="menu-custom-label">Label</label>
            <input type="text" id="menu-custom-label" class="form-control form-control-sm" maxlength="191" placeholder="Contact" autocomplete="off">
          </div>
          <div class="mb-2">
            <label class="form-label" for="menu-custom-url">URL</label>
            <input type="text" id="menu-custom-url" class="form-control form-control-sm" maxlength="500" placeholder="/contact or https://example.com" autocomplete="off">
            <div class="form-hint">A site path like /contact or a full http(s) URL. External links open in a new tab.</div>
          </div>
          <button type="button" class="btn btn-sm btn-primary" id="menu-custom-add">Add custom link</button>
        </div>
      </x-admin-card>
    </div>

    <div class="col-lg-8">
      <x-admin-card
        title="Menu structure"
        subtitle="Drag the grip to reorder within a level, or use the arrow buttons. Items nest one level deep."
      >
        <form id="menu-items-form" method="POST" action="{{ route('admin.menus.store-items', $menu, false) }}">
          @csrf
          {{-- Serialized rows are injected here right before submit --}}
          <div id="menu-items-payload"></div>

          <p id="menu-items-empty" class="text-secondary text-center py-4 mb-0 @if (count($rows) > 0) d-none @endif">
            This menu is empty. Add pages, posts, or custom links from the left, then save.
          </p>

          <ol id="menu-items-list" @class(['d-none' => count($rows) === 0])>
            @foreach ($rows as $row)
              @php
                $meta = $typeMeta[$row['type']] ?? $typeMeta['custom'];
                $hint = match ($row['type']) {
                    'page' => (string) ($pageTitles->get((int) $row['target_id']) ?? ''),
                    'post' => (string) ($postTitles->get((int) $row['target_id']) ?? ''),
                    default => (string) ($row['url'] ?? ''),
                };
              @endphp
              <li
                class="menu-item-row{{ $row['depth'] === 1 ? ' menu-item-row-child' : '' }}"
                data-cid="{{ $row['id'] }}"
                data-type="{{ $row['type'] }}"
                data-target="{{ $row['target_id'] ?? '' }}"
                data-url="{{ $row['url'] ?? '' }}"
                data-hint="{{ $hint }}"
                data-depth="{{ $row['depth'] }}"
                draggable="true"
              >
                <div class="row g-2 align-items-center">
                  <div class="col-auto">
                    <span class="menu-item-handle" title="Drag to reorder" aria-hidden="true">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
                        <path d="M9 5l0 .01" /><path d="M9 12l0 .01" /><path d="M9 19l0 .01" />
                        <path d="M15 5l0 .01" /><path d="M15 12l0 .01" /><path d="M15 19l0 .01" />
                      </svg>
                    </span>
                  </div>
                  <div class="col">
                    <input type="text" class="form-control form-control-sm menu-item-label" value="{{ $row['label'] }}" maxlength="191" aria-label="Navigation label">
                  </div>
                  <div class="col-auto">
                    <span class="badge {{ $meta['class'] }}" title="{{ $hint }}">{{ $meta['label'] }}</span>
                  </div>
                  <div class="col-auto">
                    <div class="btn-list flex-nowrap">
                      <button type="button" class="btn btn-sm" data-action="indent" title="Indent — make a child of the item above" aria-label="Indent item {{ $row['label'] }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path d="M9 6l6 6l-6 6" /></svg>
                      </button>
                      <button type="button" class="btn btn-sm" data-action="outdent" title="Outdent — move back to the top level" aria-label="Outdent item {{ $row['label'] }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path d="M15 6l-6 6l6 6" /></svg>
                      </button>
                      <button type="button" class="btn btn-sm" data-action="up" title="Move up" aria-label="Move item {{ $row['label'] }} up">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path d="M6 15l6 -6l6 6" /></svg>
                      </button>
                      <button type="button" class="btn btn-sm" data-action="down" title="Move down" aria-label="Move item {{ $row['label'] }} down">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path d="M6 9l6 6l6 -6" /></svg>
                      </button>
                      <button type="button" class="btn btn-sm text-danger" data-action="remove" title="Remove from menu" aria-label="Remove item {{ $row['label'] }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path d="M18 6l-12 12" /><path d="M6 6l12 12" /></svg>
                      </button>
                    </div>
                  </div>
                </div>
              </li>
            @endforeach
          </ol>

          <div class="d-flex justify-content-end mt-3">
            <button type="submit" class="btn btn-primary">Save menu</button>
          </div>
        </form>
      </x-admin-card>
    </div>
  </div>
@endsection

@once
  @push('styles')
    <style>
      #menu-items-list { list-style: none; margin: 0; padding: 0; }
      .menu-item-row {
        border: 1px solid var(--tblr-border-color, #e6e7e9);
        border-radius: .5rem;
        background: var(--tblr-bg-surface, #fff);
        padding: .5rem .75rem;
        margin-bottom: .5rem;
      }
      .menu-item-row-child { margin-left: 2rem; }
      .menu-item-handle { cursor: grab; display: inline-flex; color: var(--tblr-secondary, #626878); }
      .menu-item-dragging { opacity: .5; }
      .menu-item-drop-before { box-shadow: 0 -3px 0 0 var(--tblr-primary, #206bc4); }
      .menu-item-drop-after { box-shadow: 0 3px 0 0 var(--tblr-primary, #206bc4); }
      .menu-source-list { max-height: 16rem; overflow-y: auto; }
    </style>
  @endpush
@endonce

@once
  @push('scripts')
    <script>
      ;(function () {
        var list = document.getElementById('menu-items-list')
        var form = document.getElementById('menu-items-form')
        var payload = document.getElementById('menu-items-payload')
        var emptyState = document.getElementById('menu-items-empty')

        if (! list || ! form) return

        // Badges mirror the server-rendered rows.
        var BADGES = {
          page: { text: 'Page', class: 'bg-blue-lt' },
          post: { text: 'Post', class: 'bg-purple-lt' },
          custom: { text: 'Link', class: 'bg-secondary-lt' },
        }

        var GRIP = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path d="M9 5l0 .01" /><path d="M9 12l0 .01" /><path d="M9 19l0 .01" /><path d="M15 5l0 .01" /><path d="M15 12l0 .01" /><path d="M15 19l0 .01" /></svg>'
        var INDENT = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path d="M9 6l6 6l-6 6" /></svg>'
        var OUTDENT = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path d="M15 6l-6 6l6 6" /></svg>'
        var UP = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path d="M6 15l6 -6l6 6" /></svg>'
        var DOWN = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path d="M6 9l6 6l6 -6" /></svg>'
        var REMOVE = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path d="M18 6l-12 12" /><path d="M6 6l12 12" /></svg>'

        var ROW_HTML =
          '<div class="row g-2 align-items-center">' +
            '<div class="col-auto"><span class="menu-item-handle" title="Drag to reorder" aria-hidden="true">' + GRIP + '</span></div>' +
            '<div class="col"><input type="text" class="form-control form-control-sm menu-item-label" maxlength="191" aria-label="Navigation label"></div>' +
            '<div class="col-auto"><span class="badge"></span></div>' +
            '<div class="col-auto"><div class="btn-list flex-nowrap">' +
              '<button type="button" class="btn btn-sm" data-action="indent" title="Indent — make a child of the item above">' + INDENT + '</button>' +
              '<button type="button" class="btn btn-sm" data-action="outdent" title="Outdent — move back to the top level">' + OUTDENT + '</button>' +
              '<button type="button" class="btn btn-sm" data-action="up" title="Move up">' + UP + '</button>' +
              '<button type="button" class="btn btn-sm" data-action="down" title="Move down">' + DOWN + '</button>' +
              '<button type="button" class="btn btn-sm text-danger" data-action="remove" title="Remove from menu">' + REMOVE + '</button>' +
            '</div></div>' +
          '</div>'

        // Client ids: server rows keep their database id, fresh rows get
        // "n1", "n2", ... — both are request-scoped on save, the server
        // re-creates every row with new ids anyway.
        var counter = 0

        rows().forEach(function (li) {
          var match = /^n(\d+)$/.exec(li.dataset.cid || '')

          if (match) counter = Math.max(counter, parseInt(match[1], 10))
        })

        function rows() {
          return Array.prototype.slice.call(list.querySelectorAll('.menu-item-row'))
        }

        function depthOf(li) {
          return parseInt(li.dataset.depth || '0', 10)
        }

        function nextCid() {
          counter += 1

          return 'n' + counter
        }

        // ---- state ------------------------------------------------------
        // The list is edited through a tiny tree (top-level nodes with one
        // level of children) and re-rendered after every mutation, so the
        // flat row order can never drift out of sync with the nesting.
        function buildTree() {
          rows().forEach(function (li) {
            li.dataset.label = li.querySelector('.menu-item-label').value
          })

          var tree = []
          var lastParent = null

          rows().forEach(function (li) {
            var node = {
              cid: li.dataset.cid,
              type: li.dataset.type,
              target: li.dataset.target || '',
              url: li.dataset.url || '',
              hint: li.dataset.hint || '',
              label: li.dataset.label || '',
              depth: depthOf(li),
              children: [],
            }

            if (node.depth === 0 || ! lastParent) {
              node.depth = 0
              tree.push(node)
              lastParent = node
            } else {
              lastParent.children.push(node)
            }
          })

          return tree
        }

        function render(tree) {
          var flat = []

          tree.forEach(function (node) {
            flat.push(renderRow(node, 0))
            node.children.forEach(function (child) {
              flat.push(renderRow(child, 1))
            })
          })

          list.innerHTML = ''
          flat.forEach(function (li) {
            list.appendChild(li)
          })

          emptyState.classList.toggle('d-none', flat.length > 0)
          list.classList.toggle('d-none', flat.length === 0)
        }

        function renderRow(node, depth) {
          var li = document.createElement('li')

          li.className = 'menu-item-row' + (depth === 1 ? ' menu-item-row-child' : '')
          li.dataset.cid = node.cid
          li.dataset.type = node.type
          li.dataset.target = node.target
          li.dataset.url = node.url
          li.dataset.hint = node.hint
          li.dataset.depth = String(depth)
          li.setAttribute('draggable', 'true')
          li.innerHTML = ROW_HTML

          li.querySelector('.menu-item-label').value = node.label

          var badge = BADGES[node.type] || BADGES.custom
          var badgeElement = li.querySelector('.badge')

          badgeElement.textContent = badge.text
          badgeElement.classList.add(badge.class)
          badgeElement.title = node.hint

          return li
        }

        // ---- tree helpers -------------------------------------------------
        // findPath returns the chain of { arr, index } locations from the
        // root to the node — its depth is the chain length.
        function findPath(tree, cid, path) {
          path = path || []

          for (var i = 0; i < tree.length; i++) {
            if (tree[i].cid === cid) {
              return path.concat([{ arr: tree, index: i }])
            }

            var found = findPath(tree[i].children, cid, path.concat([{ arr: tree, index: i }]))

            if (found) return found
          }

          return null
        }

        function removeFrom(tree, cid) {
          var path = findPath(tree, cid)

          if (! path) return null

          var loc = path[path.length - 1]

          return loc.arr.splice(loc.index, 1)[0]
        }

        function dropNode(tree, dragCid, targetCid, before) {
          var dragPath = findPath(tree, dragCid)
          var targetPath = findPath(tree, targetCid)

          if (! dragPath || ! targetPath) return

          // Drag-and-drop reorders within a level; nesting is the arrows'
          // job. A top-level row never drops next to somebody's child.
          if (dragPath.length !== targetPath.length) return

          var dragged = removeFrom(tree, dragCid)

          if (! dragged) return

          // Re-resolve after removal — indices may have shifted.
          targetPath = findPath(tree, targetCid)

          if (! targetPath) {
            tree.push(dragged)

            return
          }

          var loc = targetPath[targetPath.length - 1]

          loc.arr.splice(loc.index + (before ? 0 : 1), 0, dragged)
        }

        // ---- row actions --------------------------------------------------
        list.addEventListener('click', function (event) {
          var button = event.target.closest('[data-action]')

          if (! button) return

          var li = button.closest('.menu-item-row')
          var tree = buildTree()
          var cid = li.dataset.cid
          var path = findPath(tree, cid)

          if (! path) return

          var loc = path[path.length - 1]
          var action = button.dataset.action

          if (action === 'remove') {
            // Deleting a parent promotes its children to the top level —
            // the same behaviour the database FK uses on delete.
            loc.arr.splice.apply(loc.arr, [loc.index, 1].concat(loc.arr[loc.index].children))

            render(tree)

            return
          }

          if (action === 'up' && loc.index > 0) {
            loc.arr.splice(loc.index - 1, 0, loc.arr.splice(loc.index, 1)[0])
          }

          if (action === 'down' && loc.index < loc.arr.length - 1) {
            loc.arr.splice(loc.index + 1, 0, loc.arr.splice(loc.index, 1)[0])
          }

          if (action === 'indent' && path.length === 1 && loc.index > 0) {
            loc.arr[loc.index - 1].children.push(loc.arr.splice(loc.index, 1)[0])
          }

          if (action === 'outdent' && path.length === 2) {
            var parent = path[0]

            parent.arr.splice(parent.index + 1, 0, loc.arr.splice(loc.index, 1)[0])
          }

          render(tree)
        })

        // ---- drag and drop -------------------------------------------------
        var dragCid = null
        var dropMode = 'before'
        var hoveredRow = null

        function clearHover() {
          if (hoveredRow) {
            hoveredRow.classList.remove('menu-item-drop-before', 'menu-item-drop-after')
            hoveredRow = null
          }
        }

        list.addEventListener('dragstart', function (event) {
          var li = event.target.closest('.menu-item-row')

          if (! li) return

          dragCid = li.dataset.cid
          li.classList.add('menu-item-dragging')
          event.dataTransfer.effectAllowed = 'move'

          try {
            event.dataTransfer.setData('text/plain', dragCid)
          } catch (error) {
            // IE-style dataTransfer rejection — dropping still works.
          }
        })

        list.addEventListener('dragover', function (event) {
          if (! dragCid) return

          event.preventDefault()
          event.dataTransfer.dropEffect = 'move'

          clearHover()

          var li = event.target.closest('.menu-item-row')

          if (li && li.dataset.cid !== dragCid) {
            var rect = li.getBoundingClientRect()

            dropMode = event.clientY - rect.top < rect.height / 2 ? 'before' : 'after'
            li.classList.add(dropMode === 'before' ? 'menu-item-drop-before' : 'menu-item-drop-after')
            hoveredRow = li
          }
        })

        list.addEventListener('drop', function (event) {
          if (! dragCid) return

          event.preventDefault()

          var li = hoveredRow

          clearHover()

          if (li) {
            var tree = buildTree()

            dropNode(tree, dragCid, li.dataset.cid, dropMode === 'before')
            render(tree)
          }

          dragCid = null
        })

        list.addEventListener('dragend', function () {
          dragCid = null
          clearHover()
        })

        // ---- adding items ----------------------------------------------------
        document.querySelectorAll('[data-menu-add]').forEach(function (button) {
          button.addEventListener('click', function () {
            var panel = button.closest('[data-menu-source]')
            var type = panel.dataset.menuSource === 'posts' ? 'post' : 'page'
            var checked = panel.querySelectorAll('input[type="checkbox"]:checked')

            if (! checked.length) return

            var tree = buildTree()

            Array.prototype.forEach.call(checked, function (input) {
              tree.push({
                cid: nextCid(),
                type: type,
                target: input.value,
                url: '',
                hint: input.dataset.title,
                label: input.dataset.title,
                depth: 0,
                children: [],
              })

              input.checked = false
            })

            render(tree)
          })
        })

        var customAdd = document.getElementById('menu-custom-add')

        if (customAdd) {
          customAdd.addEventListener('click', function () {
            var label = document.getElementById('menu-custom-label')
            var url = document.getElementById('menu-custom-url')
            var link = url.value.trim()

            if (link === '') {
              url.classList.add('is-invalid')
              url.focus()

              return
            }

            url.classList.remove('is-invalid')

            var tree = buildTree()

            tree.push({
              cid: nextCid(),
              type: 'custom',
              target: '',
              url: link,
              hint: link,
              label: label.value.trim() || link.replace(/^https?:\/\//, ''),
              depth: 0,
              children: [],
            })

            label.value = ''
            url.value = ''
            render(tree)
          })
        }

        // ---- save ----------------------------------------------------------
        // The builder posts the full flat structure; parent_id references a
        // sibling row's cid (empty for top level) and the server replaces
        // all items of the menu in one transaction.
        form.addEventListener('submit', function () {
          payload.innerHTML = ''

          var flat = rows()

          flat.forEach(function (li, index) {
            var prefix = 'items[' + index + ']'

            appendHidden(prefix + '[id]', li.dataset.cid)
            appendHidden(prefix + '[label]', li.querySelector('.menu-item-label').value)
            appendHidden(prefix + '[type]', li.dataset.type)

            if (li.dataset.target) appendHidden(prefix + '[target_id]', li.dataset.target)

            if (li.dataset.url) appendHidden(prefix + '[url]', li.dataset.url)

            if (depthOf(li) === 1) {
              for (var i = index - 1; i >= 0; i--) {
                if (depthOf(flat[i]) === 0) {
                  appendHidden(prefix + '[parent_id]', flat[i].dataset.cid)

                  break
                }
              }
            }

            appendHidden(prefix + '[order]', String(index))
          })
        })

        function appendHidden(name, value) {
          var input = document.createElement('input')

          input.type = 'hidden'
          input.name = name
          input.value = value
          payload.appendChild(input)
        }
      })()
    </script>
  @endpush
@endonce
