@extends('core/base::admin.layouts.master')

@section('title', 'Dashboard - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('pretitle', 'Overview')

@section('page-title', 'Dashboard')

@section('breadcrumbs')
  <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
@endsection

@section('content')
  <div class="row row-deck row-cards">
    {{-- Row 1: solid-color stat cards (Botble style). Themes has no admin
         page of its own yet, so it links to Settings. --}}
    <div class="col-sm-6 col-lg-3">
      <a class="card card-link text-white" href="{{ route('admin.settings.edit') }}" style="background: #d63384; border-color: #d63384">
        <div class="card-body d-flex align-items-center justify-content-between">
          <div>
            <div class="subheader text-white">Themes</div>
            <div class="h1 mb-0" data-stat="themes">{{ $stats['themes'] }}</div>
          </div>
          <span style="opacity: .4">
            <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M12 21a9 9 0 0 1 0 -18c4.97 0 9 3.582 9 8c0 1.06 -.474 2.078 -1.318 2.828c-.844 .75 -1.989 1.172 -3.182 1.172h-2.5a2 2 0 0 0 -1 3.75a1.3 1.3 0 0 1 -1 2.25" />
              <path d="M7.5 10.5l0 .01" />
              <path d="M12 7.5l0 .01" />
              <path d="M16.5 10.5l0 .01" />
            </svg>
          </span>
        </div>
      </a>
    </div>
    <div class="col-sm-6 col-lg-3">
      <a class="card card-link text-white" href="{{ route('admin.users.index') }}" style="background: #0d6efd; border-color: #0d6efd">
        <div class="card-body d-flex align-items-center justify-content-between">
          <div>
            <div class="subheader text-white">Users</div>
            <div class="h1 mb-0" data-stat="users">{{ $stats['users'] }}</div>
          </div>
          <span style="opacity: .4">
            <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M9 7a4 4 0 1 0 0 8a4 4 0 0 0 0 -8" />
              <path d="M17 11l0 .01" />
              <path d="M13 21v-2a4 4 0 0 0 -4 -4h-2a4 4 0 0 0 -4 4v2" />
              <path d="M21 21v-2a4 4 0 0 0 -3 -3.87" />
              <path d="M16 3.13a4 4 0 0 1 0 7.75" />
            </svg>
          </span>
        </div>
      </a>
    </div>
    <div class="col-sm-6 col-lg-3">
      <a class="card card-link text-white" href="{{ route('admin.plugins.index') }}" style="background: #198754; border-color: #198754">
        <div class="card-body d-flex align-items-center justify-content-between">
          <div>
            <div class="subheader text-white">Plugins</div>
            <div class="h1 mb-0" data-stat="plugins">{{ $stats['plugins'] }}</div>
          </div>
          <span style="opacity: .4">
            <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M9 2v6" />
              <path d="M15 2v6" />
              <path d="M6 8h12v3a6 6 0 0 1 -12 0z" />
              <path d="M12 17v5" />
            </svg>
          </span>
        </div>
      </a>
    </div>
    <div class="col-sm-6 col-lg-3">
      <a class="card card-link text-white" href="{{ route('admin.pages.index') }}" style="background: #fd7e14; border-color: #fd7e14">
        <div class="card-body d-flex align-items-center justify-content-between">
          <div>
            <div class="subheader text-white">Pages</div>
            <div class="h1 mb-0" data-stat="pages">{{ $stats['pages'] }}</div>
          </div>
          <span style="opacity: .4">
            <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M14 3v4a1 1 0 0 0 1 1h4" />
              <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
              <path d="M9 9l1 0" />
              <path d="M9 13l6 0" />
              <path d="M9 17l6 0" />
            </svg>
          </span>
        </div>
      </a>
    </div>

    {{-- Row 2: Site Analytics. The Botble sample also shows a world map —
         skipped in the MVP (no bundled geo dataset); the area chart takes
         the full card width instead. --}}
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Site Analytics</h3>
          <div class="card-actions">
            <div class="dropdown">
              <button class="btn dropdown-toggle" data-bs-toggle="dropdown" aria-label="Change analytics period">{{ $periodLabel }}</button>
              <div class="dropdown-menu">
                <a class="dropdown-item @selected($period === 'today')" href="{{ route('admin.dashboard', ['period' => 'today']) }}">Today</a>
                <a class="dropdown-item @selected($period === '7d')" href="{{ route('admin.dashboard', ['period' => '7d']) }}">Last 7 days</a>
                <a class="dropdown-item @selected($period === '30d')" href="{{ route('admin.dashboard', ['period' => '30d']) }}">Last 30 days</a>
              </div>
            </div>
          </div>
        </div>
        <div class="card-body">
          <div id="analytics-chart"></div>
        </div>
        <div class="card-footer">
          <div class="row g-3">
            <div class="col-6 col-lg-3">
              <div class="d-flex align-items-center">
                <span class="d-inline-flex align-items-center justify-content-center text-white rounded me-3" style="width: 44px; height: 44px; background: #be185d">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                    <path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" />
                  </svg>
                </span>
                <div>
                  <div class="h3 mb-0" data-mini="sessions">{{ $mini['sessions'] }}</div>
                  <div class="text-secondary">Sessions</div>
                </div>
              </div>
            </div>
            <div class="col-6 col-lg-3">
              <div class="d-flex align-items-center">
                <span class="d-inline-flex align-items-center justify-content-center text-white rounded me-3" style="width: 44px; height: 44px; background: #16a34a">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M9 7a4 4 0 1 0 0 8a4 4 0 0 0 0 -8" />
                    <path d="M17 11l0 .01" />
                    <path d="M13 21v-2a4 4 0 0 0 -4 -4h-2a4 4 0 0 0 -4 4v2" />
                    <path d="M21 21v-2a4 4 0 0 0 -3 -3.87" />
                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                  </svg>
                </span>
                <div>
                  <div class="h3 mb-0" data-mini="visitors">{{ $mini['visitors'] }}</div>
                  <div class="text-secondary">Visitors</div>
                </div>
              </div>
            </div>
            <div class="col-6 col-lg-3">
              <div class="d-flex align-items-center">
                <span class="d-inline-flex align-items-center justify-content-center text-white rounded me-3" style="width: 44px; height: 44px; background: #2563eb">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M4 20h16" />
                    <path d="M6 20v-6" />
                    <path d="M12 20v-11" />
                    <path d="M18 20v-9" />
                  </svg>
                </span>
                <div>
                  <div class="h3 mb-0" data-mini="pageviews">{{ $mini['pageviews'] }}</div>
                  <div class="text-secondary">Pageviews</div>
                </div>
              </div>
            </div>
            <div class="col-6 col-lg-3">
              <div class="d-flex align-items-center">
                <span class="d-inline-flex align-items-center justify-content-center text-white rounded me-3" style="width: 44px; height: 44px; background: #ea580c">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M13 3l0 7l6 0l-8 11l0 -7l-6 0l8 -11" />
                  </svg>
                </span>
                <div>
                  <div class="h3 mb-0" data-mini="bounce">{{ $mini['bounce'] }}</div>
                  <div class="text-secondary">Bounce Rate</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Row 3 --}}
    <div class="col-lg-6">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Most Visited Pages</h3>
        </div>
        <div class="table-responsive">
          <table class="table table-vcenter card-table">
            <thead>
              <tr>
                <th class="w-1">#</th>
                <th>URL</th>
                <th class="text-end">VIEWS</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($topPages as $index => $row)
                <tr>
                  <td>{{ $index + 1 }}</td>
                  <td>
                    <a href="{{ $row['href'] }}" class="text-reset">{{ $row['label'] }}</a>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-secondary ms-1" aria-hidden="true">
                      <path d="M12 6h-6a2 2 0 0 0 -2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-6" />
                      <path d="M11 13l9 -9" />
                      <path d="M15 4h5v5" />
                    </svg>
                  </td>
                  <td class="text-end">{{ $row['views'] }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="3" class="text-center text-secondary py-4">No visits recorded for this period.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Top Browsers</h3>
        </div>
        <div class="table-responsive">
          <table class="table table-vcenter card-table">
            <thead>
              <tr>
                <th class="w-1">#</th>
                <th>BROWSER</th>
                <th class="text-end">SESSIONS</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($topBrowsers as $index => $row)
                <tr>
                  <td>{{ $index + 1 }}</td>
                  <td>{{ $row['browser'] }}</td>
                  <td class="text-end">{{ $row['sessions'] }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="3" class="text-center text-secondary py-4">No visits recorded for this period.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    {{-- Row 4 --}}
    <div class="col-lg-6">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Top Referrers</h3>
        </div>
        <div class="table-responsive">
          <table class="table table-vcenter card-table">
            <thead>
              <tr>
                <th class="w-1">#</th>
                <th>URL</th>
                <th class="text-end">VIEWS</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($topReferrers as $index => $row)
                <tr>
                  <td>{{ $index + 1 }}</td>
                  <td class="text-truncate" style="max-width: 240px">{{ $row['label'] }}</td>
                  <td class="text-end">{{ $row['views'] }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="3" class="text-center text-secondary py-4">No visits recorded for this period.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Recent Posts</h3>
        </div>
        <div class="table-responsive">
          <table class="table table-vcenter card-table">
            <thead>
              <tr>
                <th class="w-1">#</th>
                <th>NAME</th>
                <th class="text-end">CREATED AT</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($recentPosts as $index => $post)
                <tr>
                  <td>{{ $index + 1 }}</td>
                  <td><a href="{{ url('/blog/'.$post->slug) }}">{{ $post->title }}</a></td>
                  <td class="text-end text-secondary">{{ $post->created_at?->format('Y-m-d H:i') }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="3" class="text-center text-secondary py-4">No posts published yet.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    {{-- Row 5 --}}
    <div class="col-lg-6">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Activity Logs</h3>
        </div>
        <div class="list-group list-group-flush">
          @forelse ($activities as $activity)
            <div class="list-group-item d-flex align-items-center">
              <span class="avatar avatar-sm me-3">{{ $activity['initials'] }}</span>
              <div class="flex-fill">
                <div>
                  <strong>{{ $activity['name'] }}</strong>
                  @if ($activity['badge'] !== null)
                    <span class="badge {{ $activity['badge']['class'] }} ms-1" data-role-badge>{{ $activity['badge']['text'] }}</span>
                  @endif
                </div>
                <div class="text-secondary small">
                  {{ $activity['text'] }} &middot; {{ $activity['time'] ?? '—' }} ({{ $activity['ip'] ?? '—' }})
                </div>
              </div>
            </div>
          @empty
            <div class="list-group-item text-center text-secondary py-4">No activity recorded yet.</div>
          @endforelse
        </div>
        <div class="card-footer text-secondary">Showing 1 to {{ count($activities) }} of {{ $activityTotal }} records</div>
      </div>
    </div>
    <div class="col-lg-6">
      {{-- MVP: request errors are always an empty state; wiring real error
           logs into this card is deferred to a later cycle. --}}
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Request Errors</h3>
        </div>
        <div class="card-body text-center py-6">
          <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-secondary" style="opacity: .5" aria-hidden="true">
            <path d="M5 11a7 7 0 1 1 14 0v7a1.78 1.78 0 0 1 -2.649 1.552l-1.995 -1.157a1.78 1.78 0 0 0 -2.712 0l-1.999 1.157a1.78 1.78 0 0 1 -2.645 -1.552z" />
            <path d="M10 10h.01" />
            <path d="M14 10h.01" />
            <path d="M10 14a3.5 3.5 0 0 0 4 0" />
          </svg>
          <p class="h4 mt-3 mb-1">No results found</p>
          <p class="text-secondary mb-0">It looks as though there are no request errors here.</p>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
  <script src="{{ asset('vendor/tabler/dist/libs/apexcharts/dist/apexcharts.min.js') }}" defer></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var element = document.getElementById('analytics-chart')

      if (! window.ApexCharts || ! element) {
        return
      }

      var data = @json($chart)

      new ApexCharts(element, {
        chart: {
          type: 'area',
          fontFamily: 'inherit',
          height: 300,
          parentHeightOffset: 0,
          toolbar: { show: false },
          animations: { enabled: false },
        },
        series: [
          { name: 'Pageviews', data: data.pageviews },
          { name: 'Sessions', data: data.sessions },
        ],
        colors: ['#60a5fa', '#f43f5e'],
        fill: { type: 'solid', opacity: .2 },
        stroke: { width: 2, curve: 'smooth', lineCap: 'round' },
        dataLabels: { enabled: false },
        tooltip: { theme: 'dark' },
        grid: { strokeDashArray: 4 },
        xaxis: {
          categories: data.labels,
          labels: { padding: 0, hideOverlappingLabels: true },
          axisBorder: { show: false },
          axisTicks: { show: false },
        },
        yaxis: {
          min: 0,
          forceNiceScale: true,
          labels: { padding: 4, formatter: function (value) { return Math.round(value) } },
        },
        legend: { show: true, position: 'bottom', offsetY: 8, markers: { width: 10, height: 10, radius: 100 } },
      }).render()
    })
  </script>
@endpush
