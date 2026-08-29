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
    <div class="page">
      <aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
        <div class="container-fluid">
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu" aria-controls="sidebar-menu" aria-expanded="false" aria-label="Toggle sidebar navigation">
            <span class="navbar-toggler-icon"></span>
          </button>
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
      <header class="navbar navbar-expand-md d-none d-lg-flex d-print-none">
        <div class="container-xl">
          <div class="navbar-nav flex-row order-md-last ms-auto">
            <div class="nav-item dropdown">
              <a href="#" class="nav-link d-flex lh-1 p-0 px-2" data-bs-toggle="dropdown" aria-label="Open user menu">
                <span class="avatar avatar-sm">{{ $adminInitials }}</span>
                <div class="d-none d-xl-block ps-2">
                  <div>{{ $adminName }}</div>
                  <div class="mt-1 small text-secondary">{{ $adminUser?->email }}</div>
                </div>
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
    @stack('scripts')
  </body>
</html>
