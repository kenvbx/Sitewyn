@extends('core/base::admin.layouts.master')

@section('title', 'Widgets - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('pretitle', 'Content')

@section('page-title', 'Widgets')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item active" aria-current="page">Widgets</li>
@endsection

@php
    $typeLabels = [
        \Sitewyn\Core\Base\Models\Widget::TYPE_PAGES => 'Pages list',
        \Sitewyn\Core\Base\Models\Widget::TYPE_RECENT_POSTS => 'Recent posts',
        \Sitewyn\Core\Base\Models\Widget::TYPE_TEXT => 'Text',
    ];
@endphp

@section('page-actions')
  @if ($areaSlug !== null)
    <div class="btn-list">
      <a href="{{ route('admin.widgets.create', ['area' => $areaSlug]) }}" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
          <path d="M12 5l0 14" />
          <path d="M5 12l14 0" />
        </svg>
        New widget
      </a>
    </div>
  @endif
@endsection

@section('content')
  @if ($areas === [])
    <div class="row row-cards">
      <div class="col-12">
        <x-admin-card
          title="Widget areas"
          subtitle="Widgets are blocks the active theme can show in named areas, such as the footer."
        >
          <div class="empty py-5">
            <p class="empty-title">Current theme does not declare widget areas</p>
            <p class="empty-subtitle text-secondary">
              Add a <code>widget_areas</code> section to the theme's <code>theme.json</code> and the areas show up here.
            </p>
          </div>
        </x-admin-card>
      </div>
    </div>
  @else
    <div class="row row-cards">
      <div class="col-12">
        <x-admin-card
          title="Widget areas"
          subtitle="Pick an area, then add the widgets the theme should display in it. Widgets render in the order listed here."
        >
          <div class="mb-3" style="max-width: 20rem;">
            <label class="form-label" for="widget-area-select">Area</label>
            <select id="widget-area-select" class="form-select" data-widget-area-jump>
              @foreach ($areas as $area)
                <option value="{{ route('admin.widgets.index', ['area' => $area['slug']]) }}" @selected($area['slug'] === $areaSlug)>
                  {{ $area['name'] }} ({{ $area['slug'] }})
                </option>
              @endforeach
            </select>
          </div>

          <div class="table-responsive">
            <table class="table table-vcenter" id="admin-widgets-table">
              <thead>
                <tr>
                  <th>Type</th>
                  <th>Heading</th>
                  <th>Details</th>
                  <th class="w-1">Order</th>
                  <th class="w-1"></th>
                </tr>
              </thead>
              <tbody>
                @forelse ($widgets as $widget)
                  <tr>
                    <td class="fw-medium">{{ $typeLabels[$widget->type] ?? $widget->type }}</td>
                    <td>{{ $widget->data['title'] ?? '—' }}</td>
                    <td class="text-secondary">
                      @if ($widget->type === \Sitewyn\Core\Base\Models\Widget::TYPE_RECENT_POSTS)
                        Shows up to {{ $widget->data['limit'] ?? 5 }} recent posts.
                      @elseif ($widget->type === \Sitewyn\Core\Base\Models\Widget::TYPE_TEXT)
                        {{ \Illuminate\Support\Str::limit(trim(strip_tags((string) ($widget->data['content'] ?? ''))), 60) ?: 'Empty text block.' }}
                      @elseif ($widget->type === \Sitewyn\Core\Base\Models\Widget::TYPE_PAGES)
                        Lists all published pages.
                      @else
                        {{ $widget->type }}
                      @endif
                    </td>
                    <td>
                      <div class="btn-list flex-nowrap">
                        <form method="POST" action="{{ route('admin.widgets.move', $widget, false) }}" class="d-inline">
                          @csrf
                          <input type="hidden" name="direction" value="up">
                          <button type="submit" class="btn btn-sm btn-icon" aria-label="Move up" @disabled($loop->first)>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
                              <path d="M9 15l3 -6l3 6" />
                            </svg>
                          </button>
                        </form>
                        <form method="POST" action="{{ route('admin.widgets.move', $widget, false) }}" class="d-inline">
                          @csrf
                          <input type="hidden" name="direction" value="down">
                          <button type="submit" class="btn btn-sm btn-icon" aria-label="Move down" @disabled($loop->last)>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
                              <path d="M9 9l3 6l3 -6" />
                            </svg>
                          </button>
                        </form>
                      </div>
                    </td>
                    <td>
                      <div class="btn-list flex-nowrap justify-content-end">
                        <a href="{{ route('admin.widgets.edit', $widget) }}" class="btn btn-sm">Edit</a>
                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#widget-delete-{{ $widget->id }}" aria-label="Delete widget">
                          Delete
                        </button>
                      </div>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="5" class="text-center text-secondary py-5">
                      No widgets in this area yet. Create one and the theme starts displaying it right away.
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <x-slot:footer>
            <div class="form-hint">
              Areas are declared by the active theme's <code>theme.json</code>. Removing the declaration from the theme hides the area and its widgets; the rows stay in the database until deleted here.
            </div>
          </x-slot:footer>
        </x-admin-card>
      </div>
    </div>

    @foreach ($widgets as $widget)
      <x-admin-modal id="widget-delete-{{ $widget->id }}" title="Delete widget">
        <p>Delete this <strong>{{ $typeLabels[$widget->type] ?? $widget->type }}</strong> widget from the <strong>{{ $areaSlug }}</strong> area?</p>
        <p class="text-secondary mb-0">The frontend stops displaying it immediately.</p>
        <form method="POST" action="{{ route('admin.widgets.destroy', $widget, false) }}" id="widget-delete-form-{{ $widget->id }}">
          @csrf
        </form>

        <x-slot:footer>
          <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger" form="widget-delete-form-{{ $widget->id }}">Delete widget</button>
        </x-slot:footer>
      </x-admin-modal>
    @endforeach
  @endif
@endsection

@push('scripts')
  <script>
    // Pure JS area jump: no framework, just follow the selected option.
    document.querySelectorAll('[data-widget-area-jump]').forEach((select) => {
      select.addEventListener('change', () => {
        window.location.href = select.value;
      });
    });
  </script>
@endpush
