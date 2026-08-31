<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>@yield('title', config('app.name', 'Sitewyn') . ' Admin')</title>
    <link rel="icon" href="{{ asset('vendor/tabler/favicon-dev.ico') }}" type="image/x-icon" />
    <link href="{{ asset('vendor/tabler/dist/css/tabler.css') }}" rel="stylesheet" />
    <link href="{{ asset('vendor/tabler/dist/css/tabler-vendors.css') }}" rel="stylesheet" />
    <link href="{{ asset('vendor/tabler/dist/css/tabler-themes.css') }}" rel="stylesheet" />
    <link href="{{ asset('vendor/tabler/preview/css/demo.css') }}" rel="stylesheet" />
    <style>
      @import url('https://rsms.me/inter/inter.css');
    </style>
    {{-- Admin chrome (header + global search) is kept inline on purpose: the
         admin panel loads Tabler straight from vendor/, and the Vite-built
         admin.css embeds Tabler again for the auth pages — linking it here
         would ship the whole framework twice. --}}
    <style>
      /* --- Header (dark slate, Botble-style) --- */
      .sitewyn-admin-header {
        background-color: #1e293b;
        border-bottom: 1px solid rgba(255, 255, 255, .08);
        color: #f1f5f9;
      }

      .sitewyn-admin-header .navbar-toggler {
        border-color: rgba(255, 255, 255, .25);
      }

      .sitewyn-admin-brand {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background-color: #2563eb;
        color: #fff;
        font-size: 1.1rem;
        font-weight: 700;
        line-height: 1;
      }

      .sitewyn-admin-brand-name {
        color: #f1f5f9;
        font-size: .875rem;
        font-weight: 600;
        letter-spacing: .02em;
      }

      .sitewyn-admin-header .nav-link.sitewyn-admin-navlink {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 40px;
        min-height: 40px;
        border-radius: .5rem;
        color: rgba(241, 245, 249, .8);
      }

      .sitewyn-admin-header .nav-link.sitewyn-admin-navlink:hover,
      .sitewyn-admin-header .nav-link.sitewyn-admin-navlink:focus {
        color: #fff;
        background-color: rgba(255, 255, 255, .08);
      }

      .sitewyn-admin-header .nav-link.sitewyn-admin-user-toggle {
        justify-content: flex-start;
        border-radius: .5rem;
      }

      .sitewyn-admin-header .nav-link.sitewyn-admin-user-toggle:hover {
        background-color: rgba(255, 255, 255, .08);
      }

      .sitewyn-admin-header .avatar {
        background-color: #2563eb;
        color: #fff;
        font-weight: 600;
      }

      /* Theme toggle: moon invites dark in light mode, sun the reverse. */
      .sitewyn-admin-theme-icon-to-dark,
      .sitewyn-admin-theme-icon-to-light {
        display: inline-flex;
      }

      .sitewyn-admin-theme-icon-to-light {
        display: none;
      }

      html[data-bs-theme='dark'] .sitewyn-admin-theme-icon-to-dark {
        display: none;
      }

      html[data-bs-theme='dark'] .sitewyn-admin-theme-icon-to-light {
        display: inline-flex;
      }

      /* --- Search trigger (fake input button) --- */
      .sitewyn-admin-search-trigger {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        min-height: 40px;
        padding: 0 .625rem;
        border: 1px solid rgba(255, 255, 255, .14);
        border-radius: .5rem;
        background-color: rgba(255, 255, 255, .08);
        color: rgba(241, 245, 249, .6);
        font-family: inherit;
        font-size: .8125rem;
        text-align: left;
        cursor: pointer;
      }

      .sitewyn-admin-search-trigger:hover,
      .sitewyn-admin-search-trigger:focus {
        color: #fff;
        background-color: rgba(255, 255, 255, .12);
      }

      .sitewyn-admin-search-trigger .icon {
        width: 1.1rem;
        height: 1.1rem;
        flex: 0 0 auto;
      }

      .sitewyn-admin-search-kbd {
        margin-left: auto;
        padding: 2px 6px;
        border: 1px solid rgba(255, 255, 255, .2);
        border-radius: 4px;
        color: rgba(241, 245, 249, .55);
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: 11px;
        white-space: nowrap;
      }

      @media (min-width: 576px) and (max-width: 1199.98px) {
        .sitewyn-admin-search-trigger {
          width: 220px;
        }
      }

      @media (min-width: 1200px) {
        .sitewyn-admin-search-trigger {
          width: 300px;
        }
      }

      @media (max-width: 575.98px) {
        .sitewyn-admin-search-trigger {
          width: 40px;
          justify-content: center;
          padding: 0;
        }
      }

      /* --- Global search modal --- */
      .sitewyn-admin-search-dialog {
        max-width: min(640px, 90vw);
        margin: 15vh auto 2rem;
      }

      .sitewyn-admin-search-modal .modal-content {
        overflow: hidden;
        border-radius: 16px;
        box-shadow: 0 25px 60px rgba(2, 6, 23, .35);
      }

      body:has(#admin-search-modal.show) .modal-backdrop {
        opacity: .6;
        backdrop-filter: blur(4px);
      }

      .sitewyn-admin-search-input-row {
        display: flex;
        align-items: center;
        gap: .625rem;
        height: 52px;
        padding: 0 1rem;
        border-bottom: 1px solid var(--tblr-border-color);
      }

      .sitewyn-admin-search-input-row .icon {
        width: 1.2rem;
        height: 1.2rem;
        flex: 0 0 auto;
      }

      .sitewyn-admin-search-input {
        flex: 1;
        height: 100%;
        border: 0;
        background: transparent;
        color: inherit;
        font-size: 1rem;
        outline: 0;
        box-shadow: none;
      }

      .sitewyn-admin-search-esc-hint {
        padding: 2px 7px;
        border: 1px solid var(--tblr-border-color);
        border-radius: 4px;
        color: var(--tblr-secondary);
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: 11px;
      }

      .sitewyn-admin-search-results {
        max-height: 50vh;
        padding: .5rem;
        overflow-y: auto;
      }

      .sitewyn-admin-search-group {
        padding: .5rem .625rem .25rem;
        color: var(--tblr-secondary);
        font-size: 11px;
        font-weight: 600;
        letter-spacing: .06em;
        text-transform: uppercase;
      }

      .sitewyn-admin-search-item {
        display: flex;
        align-items: center;
        gap: .625rem;
        width: 100%;
        padding: .5rem .625rem;
        border: 0;
        border-left: 2px solid transparent;
        border-radius: .5rem;
        background: transparent;
        color: inherit;
        font-family: inherit;
        font-size: .875rem;
        text-align: left;
        cursor: pointer;
      }

      .sitewyn-admin-search-item:hover {
        background: rgba(15, 23, 42, .05);
      }

      .sitewyn-admin-search-item.active {
        border-left-color: #2563eb;
        background: rgba(37, 99, 235, .08);
      }

      .sitewyn-admin-search-item-icon {
        display: inline-flex;
        color: var(--tblr-secondary);
      }

      .sitewyn-admin-search-item-icon .icon {
        width: 1.1rem;
        height: 1.1rem;
        flex: 0 0 auto;
      }

      .sitewyn-admin-search-item-title {
        font-weight: 600;
      }

      .sitewyn-admin-search-item-subtitle {
        color: var(--tblr-secondary);
        font-size: .75rem;
        overflow-wrap: anywhere;
      }

      .sitewyn-admin-search-empty {
        padding: 1.5rem;
        color: var(--tblr-secondary);
        text-align: center;
      }

      .sitewyn-admin-search-footer {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: .5rem .875rem;
        border-top: 1px solid var(--tblr-border-color);
        background: var(--tblr-bg-surface-secondary);
      }

      .sitewyn-admin-search-hint {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        color: var(--tblr-secondary);
        font-size: 11px;
      }

      .sitewyn-admin-search-hint kbd {
        min-width: 18px;
        padding: 1px 5px;
        border: 1px solid var(--tblr-border-color);
        border-bottom-width: 2px;
        border-radius: 4px;
        background: transparent;
        color: var(--tblr-secondary);
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: 10px;
        text-align: center;
      }
    </style>
    @stack('styles')
  </head>
  <body>
    @php
        $adminUser = auth('admin')->user();
        $adminName = $adminUser?->name ?: 'Administrator';
        $adminInitials = collect(explode(' ', $adminName))->filter()->map(fn (string $part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))->take(2)->implode('') ?: 'AD';
        $adminMenuItems = app(\Sitewyn\Core\Base\Support\AdminMenuRegistry::class)->visibleFor($adminUser);
        $adminFlash = app(\Sitewyn\Core\Base\Support\AdminFlash::class)->current();
    @endphp
    <script src="{{ asset('vendor/tabler/dist/js/tabler-theme.js') }}"></script>
    {{-- Restore the admin theme before first paint (tabler-theme.js runs
         first and defaults to light; our key wins when present). --}}
    <script>
      ;(function () {
        var storedTheme = localStorage.getItem('sitewyn-admin-theme')

        if (storedTheme === 'dark' || storedTheme === 'light') {
          document.documentElement.setAttribute('data-bs-theme', storedTheme)
        }
      })()
    </script>
    <div class="page">
      <aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
        <div class="container-fluid">
          <div class="navbar-brand navbar-brand-autodark">
            <a href="{{ route('admin.dashboard') }}" aria-label="{{ config('app.name', 'Sitewyn') }}">
              <span class="navbar-brand-image fw-bold fs-2">{{ config('app.name', 'Sitewyn') }}</span>
            </a>
          </div>
          <nav class="collapse navbar-collapse" id="sidebar-menu" aria-label="Sidebar">
            <ul class="navbar-nav pt-lg-3">
              @foreach ($adminMenuItems as $item)
                @php
                    $children = collect($item['children']);
                    $href = $item['route'] ? route($item['route']) : ($item['url'] ?? '#');
                @endphp
                <li class="nav-item {{ $children->isNotEmpty() ? 'dropdown' : '' }} {{ $item['active'] ? 'active' : '' }}">
                  <a class="nav-link {{ $children->isNotEmpty() ? 'dropdown-toggle' : '' }}" href="{{ $children->isNotEmpty() ? '#sidebar-' . $item['id'] : $href }}" @if ($children->isNotEmpty()) data-bs-toggle="dropdown" data-bs-auto-close="false" role="button" aria-expanded="{{ $item['active'] ? 'true' : 'false' }}" @endif>
                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                      @include('core/base::admin.partials.icon', ['name' => $item['icon']])
                    </span>
                    <span class="nav-link-title">{{ $item['title'] }}</span>
                  </a>
                  @if ($children->isNotEmpty())
                    <div class="dropdown-menu {{ $item['active'] ? 'show' : '' }}">
                      @foreach ($children as $child)
                        @php
                            $childHref = $child['route'] ? route($child['route']) : ($child['url'] ?? '#');
                        @endphp
                        <a class="dropdown-item {{ $child['active'] ? 'active' : '' }}" href="{{ $childHref }}">{{ $child['title'] }}</a>
                      @endforeach
                    </div>
                  @endif
                </li>
              @endforeach
            </ul>
          </nav>
        </div>
      </aside>
      <header class="navbar navbar-expand-md navbar-dark d-print-none sitewyn-admin-header" data-bs-theme="dark">
        <div class="container-xl">
          {{-- Sidebar collapse: same #sidebar-menu target the vertical navbar
               used to carry, only reachable below lg (where the sidebar nav
               is collapsed) — behavior unchanged. --}}
          <button class="navbar-toggler d-lg-none me-2" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu" aria-controls="sidebar-menu" aria-expanded="false" aria-label="Toggle sidebar navigation">
            <span class="navbar-toggler-icon"></span>
          </button>
          <a href="{{ route('admin.dashboard') }}" class="navbar-brand d-flex align-items-center gap-2 p-0" aria-label="{{ config('app.name', 'Sitewyn') }} admin dashboard">
            <span class="sitewyn-admin-brand" aria-hidden="true">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(config('app.name', 'Sitewyn'), 0, 1)) }}</span>
            <span class="sitewyn-admin-brand-name d-none d-sm-inline">{{ config('app.name', 'Sitewyn') }}</span>
          </a>
          <div class="navbar-nav flex-row align-items-center order-md-last ms-auto">
            <div class="nav-item">
              <button type="button" class="sitewyn-admin-search-trigger" data-admin-search data-admin-search-url="{{ route('admin.search') }}" aria-haspopup="dialog" aria-label="Open admin search">
                <span class="d-inline-flex">@include('core/base::admin.partials.icon', ['name' => 'search'])</span>
                <span class="d-none d-sm-inline">Search</span>
                <span class="sitewyn-admin-search-kbd d-none d-xl-inline">Ctrl/⌘ K</span>
              </button>
            </div>
            <div class="nav-item ms-1">
              <a href="{{ url('/') }}" target="_blank" rel="noopener" class="nav-link sitewyn-admin-navlink" aria-label="View website (opens in a new tab)">
                @include('core/base::admin.partials.icon', ['name' => 'globe'])
                <span class="d-none d-xl-inline ms-1">View website</span>
              </a>
            </div>
            <div class="nav-item ms-1">
              <button type="button" class="nav-link sitewyn-admin-navlink" data-admin-theme-toggle aria-label="Toggle light and dark theme">
                <span class="sitewyn-admin-theme-icon-to-dark">@include('core/base::admin.partials.icon', ['name' => 'moon'])</span>
                <span class="sitewyn-admin-theme-icon-to-light">@include('core/base::admin.partials.icon', ['name' => 'sun'])</span>
              </button>
            </div>
            <div class="nav-item dropdown ms-1">
              <a href="#" class="nav-link sitewyn-admin-navlink" data-bs-toggle="dropdown" role="button" aria-expanded="false" aria-label="Notifications">
                @include('core/base::admin.partials.icon', ['name' => 'bell'])
              </a>
              <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                <div class="dropdown-item text-secondary small">No notifications yet.</div>
              </div>
            </div>
            <div class="nav-item dropdown ms-1">
              <a href="#" class="nav-link d-flex align-items-center lh-1 p-0 ps-2 sitewyn-admin-user-toggle" data-bs-toggle="dropdown" aria-label="Open user menu">
                <span class="avatar avatar-sm">{{ $adminInitials }}</span>
                <div class="d-none d-xl-block ps-2">
                  <div class="fw-bold">{{ $adminName }}</div>
                  <div class="mt-1 small text-secondary">{{ $adminUser?->email }}</div>
                </div>
                <span class="d-none d-xl-inline-flex ms-2">@include('core/base::admin.partials.icon', ['name' => 'chevron-down'])</span>
              </a>
              <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                <form method="POST" action="{{ route('admin.logout') }}">
                  @csrf
                  <button type="submit" class="dropdown-item">Logout</button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </header>
      <div class="page-wrapper">
        <div class="page-header d-print-none">
          <div class="container-xl">
            @hasSection('breadcrumbs')
              <ol class="breadcrumb breadcrumb-arrows mb-2" aria-label="breadcrumbs">
                @yield('breadcrumbs')
              </ol>
            @endif
            <div class="row g-2 align-items-center">
              <div class="col">
                <div class="page-pretitle">@yield('pretitle', 'Admin')</div>
                <h2 class="page-title">@yield('page-title')</h2>
              </div>
              <div class="col-auto ms-auto d-print-none">@yield('page-actions')</div>
            </div>
          </div>
        </div>
        <div class="page-body">
          <div class="container-xl">
            @yield('content')
          </div>
        </div>
      </div>
    </div>
    {{-- Global search modal (opened by the header trigger or Ctrl/⌘ + K).
         Bootstrap Modal JS (bundled in tabler.js) provides the backdrop,
         Esc and outside-click closing; the palette logic is below. --}}
    <div class="modal modal-blur sitewyn-admin-search-modal" id="admin-search-modal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog sitewyn-admin-search-dialog" role="document">
        <div class="modal-content">
          <div class="sitewyn-admin-search-input-row">
            <span class="d-inline-flex text-secondary">@include('core/base::admin.partials.icon', ['name' => 'search'])</span>
            <input type="text" id="admin-search-input" class="sitewyn-admin-search-input" placeholder="Search pages, posts, users..." autocomplete="off" spellcheck="false" aria-label="Search pages, posts, users" />
            <span class="sitewyn-admin-search-esc-hint" aria-hidden="true">esc</span>
          </div>
          <div class="sitewyn-admin-search-results" id="admin-search-results" role="listbox" aria-label="Search results"></div>
          <div class="sitewyn-admin-search-footer">
            <span class="sitewyn-admin-search-hint"><kbd>↑</kbd><kbd>↓</kbd> to navigate</span>
            <span class="sitewyn-admin-search-hint"><kbd>↵</kbd> to select</span>
            <span class="sitewyn-admin-search-hint ms-auto"><kbd>esc</kbd> to close</span>
          </div>
        </div>
      </div>
    </div>
    @if ($adminFlash)
      <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <x-admin-toast id="admin-flash-toast" :type="$adminFlash['type']" :title="$adminFlash['title']" time="{{ __('now') }}" autohide>
          {{ $adminFlash['message'] }}
        </x-admin-toast>
      </div>
    @endif
    <script src="{{ asset('vendor/tabler/dist/js/tabler.js') }}" defer></script>
    <script src="{{ asset('vendor/tabler/preview/js/demo.js') }}" defer></script>
    @if ($adminFlash)
      <script>
        document.addEventListener('DOMContentLoaded', function () {
          var toast = document.getElementById('admin-flash-toast')

          if (toast && window.tabler && window.tabler.bootstrap) {
            window.tabler.bootstrap.Toast.getOrCreateInstance(toast).show()
          }
        })
      </script>
    @endif
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        var forms = document.querySelectorAll('form[data-admin-validate]')

        forms.forEach(function (form) {
          var confirmFields = form.querySelectorAll('[data-admin-confirm]')

          var validateConfirmFields = function () {
            confirmFields.forEach(function (confirmField) {
              var targetName = confirmField.getAttribute('data-admin-confirm')
              var targetField = form.elements[targetName] || document.getElementById(targetName)
              var message = confirmField.getAttribute('data-admin-confirm-message') || 'This field does not match.'

              if (! targetField || ! confirmField.value) {
                confirmField.setCustomValidity('')

                return
              }

              confirmField.setCustomValidity(confirmField.value === targetField.value ? '' : message)
            })
          }

          confirmFields.forEach(function (confirmField) {
            var targetName = confirmField.getAttribute('data-admin-confirm')
            var targetField = form.elements[targetName] || document.getElementById(targetName)

            confirmField.addEventListener('input', validateConfirmFields)

            if (targetField) {
              targetField.addEventListener('input', validateConfirmFields)
            }
          })

          form.addEventListener('submit', function (event) {
            validateConfirmFields()

            if (! form.checkValidity()) {
              event.preventDefault()
              event.stopPropagation()
            }

            form.classList.add('was-validated')
          }, false)
        })
      })
    </script>
    @once
      @push('scripts')
        <script>
          ;(function () {
            // --- Admin theme toggle (persisted to sitewyn-admin-theme) ---
            var themeToggle = document.querySelector('[data-admin-theme-toggle]')

            if (themeToggle) {
              themeToggle.addEventListener('click', function () {
                var root = document.documentElement
                var next = root.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark'

                root.setAttribute('data-bs-theme', next)
                localStorage.setItem('sitewyn-admin-theme', next)
              })
            }

            // --- Global search modal ---
            var modal = document.getElementById('admin-search-modal')
            var trigger = document.querySelector('[data-admin-search]')
            var input = document.getElementById('admin-search-input')
            var results = document.getElementById('admin-search-results')

            if (! modal || ! trigger || ! input || ! results) {
              return
            }

            // Icon whitelist — mirrors admin/partials/icon.blade.php; unknown
            // names fall back to the default circle.
            var iconPaths = {
              page: '<path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" /><path d="M9 9l1 0" /><path d="M9 13l6 0" /><path d="M9 17l6 0" />',
              post: '<path d="M6 4h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2z" /><path d="M4 10h16" /><path d="M8 14h8" /><path d="M8 18h5" />',
              users: '<path d="M9 7a4 4 0 1 0 0 8a4 4 0 0 0 0 -8" /><path d="M17 11l0 .01" /><path d="M13 21v-2a4 4 0 0 0 -4 -4h-2a4 4 0 0 0 -4 4v2" /><path d="M21 21v-2a4 4 0 0 0 -3 -3.87" /><path d="M16 3.13a4 4 0 0 1 0 7.75" />',
              home: '<path d="M5 12l-2 0l9 -9l9 9l-2 0" /><path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7" /><path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6" />',
              media: '<path d="M15 8h.01" /><path d="M3 6a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v12a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3v-12z" /><path d="M3 16l5 -5c.928 -.893 2.072 -.893 3 0l5 5" /><path d="M14 14l1 -1c.928 -.893 2.072 -.893 3 0l3 3" />',
              menu: '<path d="M4 6h16" /><path d="M4 12h16" /><path d="M4 18h16" />',
              widget: '<path d="M4 4h6v6h-6z" /><path d="M14 4h6v6h-6z" /><path d="M4 14h6v6h-6z" /><path d="M14 14h6v6h-6z" />',
              plugin: '<path d="M4 7h3a1 1 0 0 0 1 -1v-1a2 2 0 0 1 4 0v1a1 1 0 0 0 1 1h3a1 1 0 0 1 1 1v3a1 1 0 0 0 1 1h1a2 2 0 0 1 0 4h-1a1 1 0 0 0 -1 1v3a1 1 0 0 1 -1 1h-3a1 1 0 0 1 -1 -1v-1a2 2 0 0 0 -4 0v1a1 1 0 0 1 -1 1h-3a1 1 0 0 1 -1 -1v-3a1 1 0 0 1 1 -1h1a2 2 0 0 0 0 -4h-1a1 1 0 0 1 -1 -1v-3a1 1 0 0 1 1 -1" />',
              settings: '<path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065z" /><path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" />',
              audit: '<path d="M12 8l0 4l2 2" /><path d="M3.05 11a9 9 0 1 1 .5 4" /><path d="M3 20v-5h5" />',
              globe: '<circle cx="12" cy="12" r="10" /><line x1="2" y1="12" x2="22" y2="12" /><path d="M12 2a15.3 15.3 0 0 1 4 10a15.3 15.3 0 0 1 -4 10a15.3 15.3 0 0 1 -4 -10a15.3 15.3 0 0 1 4 -10z" />',
              search: '<circle cx="11" cy="11" r="8" /><line x1="21" y1="21" x2="16.65" y2="16.65" />',
              default: '<path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />',
            }

            var icon = function (name) {
              var paths = iconPaths[name] || iconPaths.default

              return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">' + paths + '</svg>'
            }

            var items = []
            var activeIndex = -1
            var debounceTimer = null
            var requestToken = 0
            var modalInstance = null

            var bootstrap = function () {
              return (window.tabler && window.tabler.bootstrap) || window.bootstrap || null
            }

            var openSearch = function () {
              var bs = bootstrap()

              if (! bs) {
                return
              }

              modalInstance = bs.Modal.getOrCreateInstance(modal)
              modalInstance.show()
            }

            var setActive = function (index) {
              if (activeIndex >= 0 && items[activeIndex]) {
                items[activeIndex].element.classList.remove('active')
                items[activeIndex].element.removeAttribute('aria-selected')
              }

              activeIndex = index

              if (activeIndex >= 0 && items[activeIndex]) {
                items[activeIndex].element.classList.add('active')
                items[activeIndex].element.setAttribute('aria-selected', 'true')
                items[activeIndex].element.scrollIntoView({ block: 'nearest' })
              }
            }

            var move = function (delta) {
              if (! items.length) {
                return
              }

              var next = activeIndex + delta

              if (next < 0) {
                next = 0
              }

              if (next > items.length - 1) {
                next = items.length - 1
              }

              if (next !== activeIndex) {
                setActive(next)
              }
            }

            var select = function () {
              if (activeIndex >= 0 && items[activeIndex]) {
                window.location.href = items[activeIndex].url
              }
            }

            var renderLoading = function () {
              results.innerHTML = '<div class="sitewyn-admin-search-empty"><span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Searching…</div>'
            }

            var render = function (payload) {
              results.innerHTML = ''
              items = []
              activeIndex = -1

              var groups = (payload && payload.groups) || []

              if (! groups.length) {
                results.innerHTML = '<div class="sitewyn-admin-search-empty"><div class="fw-bold">No results found</div><div class="small mt-1">Try adjusting your search or filters</div></div>'

                return
              }

              groups.forEach(function (group) {
                var header = document.createElement('div')

                header.className = 'sitewyn-admin-search-group'
                header.textContent = String(group.label || '').toUpperCase() + ' (' + (group.items || []).length + ')'
                results.appendChild(header)

                ;(group.items || []).forEach(function (item) {
                  var row = document.createElement('button')

                  row.type = 'button'
                  row.className = 'sitewyn-admin-search-item'
                  row.setAttribute('role', 'option')

                  // Icon HTML comes from the whitelist above; titles and
                  // subtitles are user content, so they go in via textContent.
                  var iconSpan = document.createElement('span')

                  iconSpan.className = 'sitewyn-admin-search-item-icon'
                  iconSpan.innerHTML = icon(item.icon)
                  row.appendChild(iconSpan)

                  var text = document.createElement('span')

                  var title = document.createElement('span')

                  title.className = 'sitewyn-admin-search-item-title d-block'
                  title.textContent = item.title || ''
                  text.appendChild(title)

                  if (item.subtitle) {
                    var subtitle = document.createElement('span')

                    subtitle.className = 'sitewyn-admin-search-item-subtitle d-block'
                    subtitle.textContent = item.subtitle
                    text.appendChild(subtitle)
                  }

                  row.appendChild(text)
                  row.addEventListener('click', function () {
                    window.location.href = item.url
                  })
                  results.appendChild(row)

                  items.push({ url: item.url, element: row })
                })
              })
            }

            var fetchResults = function (query) {
              var token = ++requestToken
              var endpoint = trigger.getAttribute('data-admin-search-url')

              if (! endpoint) {
                return
              }

              fetch(endpoint + '?q=' + encodeURIComponent(query), {
                headers: {
                  'Accept': 'application/json',
                  'X-Requested-With': 'XMLHttpRequest',
                },
              })
                .then(function (response) {
                  return response.ok ? response.json() : { groups: [] }
                })
                .then(function (payload) {
                  if (token === requestToken) {
                    render(payload)
                  }
                })
                .catch(function () {
                  if (token === requestToken) {
                    render({ groups: [] })
                  }
                })
            }

            var queryInput = function (query) {
              window.clearTimeout(debounceTimer)

              if (query === '') {
                fetchResults(query)

                return
              }

              renderLoading()
              debounceTimer = window.setTimeout(function () {
                fetchResults(query)
              }, 200)
            }

            trigger.addEventListener('click', openSearch)

            input.addEventListener('input', function () {
              queryInput(input.value.trim())
            })

            input.addEventListener('keydown', function (event) {
              if (event.key === 'ArrowDown') {
                event.preventDefault()
                move(1)
              } else if (event.key === 'ArrowUp') {
                event.preventDefault()
                move(-1)
              } else if (event.key === 'Enter') {
                if (activeIndex >= 0) {
                  event.preventDefault()
                  select()
                }
              }
            })

            document.addEventListener('keydown', function (event) {
              if ((event.metaKey || event.ctrlKey) && ! event.altKey && ! event.shiftKey && String(event.key).toLowerCase() === 'k') {
                event.preventDefault()
                openSearch()
              }
            })

            modal.addEventListener('shown.bs.modal', function () {
              input.value = ''
              fetchResults('')
              input.focus()
            })

            modal.addEventListener('hidden.bs.modal', function () {
              items = []
              activeIndex = -1
              results.innerHTML = ''
              input.value = ''
              window.clearTimeout(debounceTimer)

              if (trigger) {
                trigger.focus()
              }
            })
          })()
        </script>
      @endpush
    @endonce
    @stack('scripts')
  </body>
</html>
