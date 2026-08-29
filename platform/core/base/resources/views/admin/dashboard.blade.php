@extends('core/base::admin.layouts.master')

@section('title', 'Dashboard - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('pretitle', 'Overview')

@section('page-title', 'Dashboard')

@section('breadcrumbs')
  <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
@endsection

@section('page-actions')
  <div class="btn-list">
    <a href="{{ route('admin.users.index') }}" class="btn btn-primary">
      <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-users" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
        <path d="M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" />
        <path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
        <path d="M21 21v-2a4 4 0 0 0 -3 -3.85" />
      </svg>
      Users
    </a>
  </div>
@endsection

@section('content')
  <div class="row row-deck row-cards">
    <div class="col-sm-6 col-lg-3">
      <div class="card">
        <div class="card-body">
          <div class="subheader">Content</div>
          <div class="d-flex align-items-baseline">
            <div class="h1 mb-0 me-2">128</div>
            <div class="me-auto">
              <span class="text-green d-inline-flex align-items-center lh-1">
                12%
                <svg xmlns="http://www.w3.org/2000/svg" class="icon ms-1 icon-sm" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                  <path d="M12 5l0 14" />
                  <path d="M18 11l-6 -6" />
                  <path d="M6 11l6 -6" />
                </svg>
              </span>
            </div>
          </div>
          <div class="text-secondary mt-2">Published pages and posts</div>
        </div>
        <div id="chart-content" class="chart-sm"></div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card">
        <div class="card-body">
          <div class="subheader">Media files</div>
          <div class="d-flex align-items-baseline">
            <div class="h1 mb-0 me-2">846</div>
            <div class="me-auto">
              <span class="text-green d-inline-flex align-items-center lh-1">
                8%
                <svg xmlns="http://www.w3.org/2000/svg" class="icon ms-1 icon-sm" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                  <path d="M12 5l0 14" />
                  <path d="M18 11l-6 -6" />
                  <path d="M6 11l6 -6" />
                </svg>
              </span>
            </div>
          </div>
          <div class="text-secondary mt-2">Files managed in library</div>
        </div>
        <div id="chart-media" class="chart-sm"></div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card">
        <div class="card-body">
          <div class="subheader">Users</div>
          <div class="d-flex align-items-baseline">
            <div class="h1 mb-0 me-2">24</div>
            <div class="me-auto">
              <span class="text-yellow d-inline-flex align-items-center lh-1">
                0%
                <svg xmlns="http://www.w3.org/2000/svg" class="icon ms-1 icon-sm" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                  <path d="M5 12l14 0" />
                </svg>
              </span>
            </div>
          </div>
          <div class="text-secondary mt-2">Admin accounts and roles</div>
        </div>
        <div id="chart-users" class="chart-sm"></div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card">
        <div class="card-body">
          <div class="subheader">System health</div>
          <div class="d-flex align-items-baseline">
            <div class="h1 mb-0 me-2">99.9%</div>
            <div class="me-auto">
              <span class="text-green d-inline-flex align-items-center lh-1">Good</span>
            </div>
          </div>
          <div class="text-secondary mt-2">Core services are available</div>
        </div>
        <div class="progress progress-sm">
          <div class="progress-bar bg-primary" style="width: 99.9%" role="progressbar" aria-valuenow="99.9" aria-valuemin="0" aria-valuemax="100" aria-label="99.9% Complete">
            <span class="visually-hidden">99.9% Complete</span>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Activity</h3>
        </div>
        <div class="card-body">
          <div id="chart-activity" class="chart-lg"></div>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Quick links</h3>
        </div>
        <div class="list-group list-group-flush">
          <a href="{{ route('admin.users.index') }}" class="list-group-item list-group-item-action">
            <div class="row align-items-center">
              <div class="col-auto">
                <span class="status-dot status-dot-animated bg-green d-block"></span>
              </div>
              <div class="col text-truncate">
                <span class="text-body d-block">Users</span>
                <div class="d-block text-secondary text-truncate mt-n1">Manage admin accounts</div>
              </div>
            </div>
          </a>
          <a href="{{ route('admin.roles.index') }}" class="list-group-item list-group-item-action">
            <div class="row align-items-center">
              <div class="col-auto">
                <span class="status-dot bg-blue d-block"></span>
              </div>
              <div class="col text-truncate">
                <span class="text-body d-block">Roles</span>
                <div class="d-block text-secondary text-truncate mt-n1">Control permission groups</div>
              </div>
            </div>
          </a>
          <a href="{{ route('admin.settings.edit') }}" class="list-group-item list-group-item-action">
            <div class="row align-items-center">
              <div class="col-auto">
                <span class="status-dot bg-azure d-block"></span>
              </div>
              <div class="col text-truncate">
                <span class="text-body d-block">Settings</span>
                <div class="d-block text-secondary text-truncate mt-n1">Configure site basics</div>
              </div>
            </div>
          </a>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
  <script src="{{ asset('vendor/tabler/dist/libs/apexcharts/dist/apexcharts.min.js') }}" defer></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      if (! window.ApexCharts) {
        return
      }

      var sparklineOptions = function (element, color, data) {
        return {
          chart: {
            type: 'area',
            fontFamily: 'inherit',
            height: 40,
            sparkline: { enabled: true },
            animations: { enabled: false },
          },
          dataLabels: { enabled: false },
          fill: { opacity: .16, type: 'solid' },
          stroke: { width: 2, lineCap: 'round', curve: 'smooth' },
          series: [{ name: element, data: data }],
          tooltip: { theme: 'dark' },
          grid: { strokeDashArray: 4 },
          colors: [color],
          xaxis: { labels: { padding: 0 } },
          yaxis: { labels: { padding: 4 } },
        }
      }

      ;[
        ['#chart-content', 'var(--tblr-primary)', [37, 35, 44, 52, 49, 61, 70, 75]],
        ['#chart-media', 'var(--tblr-green)', [22, 26, 31, 29, 38, 42, 46, 53]],
        ['#chart-users', 'var(--tblr-yellow)', [18, 19, 20, 20, 22, 23, 24, 24]],
      ].forEach(function (chart) {
        var element = document.querySelector(chart[0])

        if (element) {
          new ApexCharts(element, sparklineOptions(chart[0], chart[1], chart[2])).render()
        }
      })

      var activity = document.querySelector('#chart-activity')

      if (activity) {
        new ApexCharts(activity, {
          chart: {
            type: 'bar',
            fontFamily: 'inherit',
            height: 320,
            parentHeightOffset: 0,
            toolbar: { show: false },
            animations: { enabled: false },
          },
          plotOptions: { bar: { borderRadius: 4, columnWidth: '45%' } },
          dataLabels: { enabled: false },
          fill: { opacity: 1 },
          series: [
            { name: 'Published', data: [44, 55, 41, 67, 22, 43, 21] },
            { name: 'Drafts', data: [13, 23, 20, 8, 13, 27, 33] },
          ],
          tooltip: { theme: 'dark' },
          grid: {
            padding: { top: -20, right: 0, left: -4, bottom: -4 },
            strokeDashArray: 4,
          },
          xaxis: {
            categories: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            labels: { padding: 0 },
            axisBorder: { show: false },
          },
          yaxis: { labels: { padding: 4 } },
          colors: ['var(--tblr-primary)', 'var(--tblr-green)'],
          legend: { show: true, position: 'bottom', offsetY: 12, markers: { width: 10, height: 10, radius: 100 } },
        }).render()
      }
    })
  </script>
@endpush
