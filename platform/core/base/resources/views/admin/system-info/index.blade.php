@extends('core/base::admin.layouts.master')

@section('title', 'System Information - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('pretitle', 'System')

@section('page-title', 'System Information')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.system') }}">Platform Administration</a></li>
  <li class="breadcrumb-item active" aria-current="page">System Information</li>
@endsection

@section('content')
  <div class="alert alert-info mb-4" role="alert">
    <div class="d-flex gap-2">
      <div>@include('core/base::admin.partials.icon', ['name' => 'info-circle'])</div>
      <div class="w-100">
        <h4 class="alert-title">Please share this information for troubleshooting</h4>
        <button type="button" class="btn btn-primary" data-system-report-toggle>Get System Report</button>

        <div class="mt-3 d-none" data-system-report-panel>
          <textarea class="form-control font-monospace" rows="10" readonly data-system-report>{{ $report }}</textarea>
          <button type="button" class="btn mt-2" data-system-report-copy>
            @include('core/base::admin.partials.icon', ['name' => 'copy'])
            <span class="ms-2" data-system-report-copy-label>Copy Report</span>
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header">
          <h2 class="card-title">Installed packages and their version numbers</h2>
        </div>
        <div class="card-body border-bottom">
          <div class="col-sm-4">
            <input type="search" class="form-control" placeholder="Search..." aria-label="Search packages" data-package-search>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-vcenter card-table" data-package-table>
            <thead>
              <tr>
                <th>Package Name : Version</th>
                <th>Dependency Name : Version</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($packages as $package)
                <tr data-package-row>
                  <td class="fw-medium">
                    {{ $package['name'] }}:
                    <span class="badge bg-primary text-primary-fg">{{ $package['version'] }}</span>
                  </td>
                  <td>
                    @if ($package['dependencies'] === [])
                      <span class="text-muted">No dependencies</span>
                    @else
                      <ul class="mb-0">
                        @foreach ($package['dependencies'] as $dependency => $version)
                          <li>
                            {{ $dependency }}:
                            <span class="badge bg-primary text-primary-fg">{{ $version }}</span>
                          </li>
                        @endforeach
                      </ul>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="2" class="text-center text-muted py-5">No packages found.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      @include('core/base::admin.system-info.partials.info-card', ['title' => 'System Environment', 'rows' => $systemEnvironment])
      @include('core/base::admin.system-info.partials.info-card', ['title' => 'Server Environment', 'rows' => $serverEnvironment])
      @include('core/base::admin.system-info.partials.info-card', ['title' => 'Database Information', 'rows' => $databaseInformation])
      @include('core/base::admin.system-info.partials.info-card', ['title' => 'PHP Configuration', 'rows' => $phpConfiguration])
    </div>
  </div>
@endsection

@push('scripts')
  <script>
    document.querySelectorAll('[data-system-report-toggle]').forEach(function (button) {
      button.addEventListener('click', function () {
        document.querySelector('[data-system-report-panel]')?.classList.remove('d-none')
      })
    })

    document.querySelectorAll('[data-system-report-copy]').forEach(function (button) {
      button.addEventListener('click', function () {
        var report = document.querySelector('[data-system-report]')
        var label = button.querySelector('[data-system-report-copy-label]')
        var originalLabel = label ? label.textContent : ''

        if (!report) {
          return
        }

        navigator.clipboard.writeText(report.value).then(function () {
          if (label) {
            label.textContent = 'Copied'

            window.setTimeout(function () {
              label.textContent = originalLabel
            }, 1500)
          }
        })
      })
    })

    document.querySelectorAll('[data-package-search]').forEach(function (input) {
      input.addEventListener('input', function () {
        var query = input.value.toLowerCase()

        document.querySelectorAll('[data-package-row]').forEach(function (row) {
          row.classList.toggle('d-none', !row.textContent.toLowerCase().includes(query))
        })
      })
    })
  </script>
@endpush
