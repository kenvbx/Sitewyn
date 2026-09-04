@extends('core/base::admin.layouts.master')

@section('title', 'Blog - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.settings.edit') }}">Settings</a></li>
  <li class="breadcrumb-item">Others</li>
  <li class="breadcrumb-item active" aria-current="page">Blog</li>
@endsection

@section('content')
  @if (session('status'))
    <div class="alert alert-success" role="alert">{{ session('status') }}</div>
  @endif

  <form id="blog-settings-form" method="POST" action="{{ route('admin.settings.blog.update', [], false) }}" class="needs-validation" data-admin-validate novalidate>
    @csrf
    @method('PUT')
    <input type="hidden" name="site_name" value="{{ $baseSettings['site_name'] }}">
    <input type="hidden" name="site_logo" value="{{ $baseSettings['site_logo'] }}">
    <input type="hidden" name="robots_txt" value="{{ $baseSettings['robots_txt'] }}">
    <input type="hidden" name="active_theme" value="{{ $baseSettings['active_theme'] }}">

    <div class="row mb-5 d-block d-md-flex">
      <div class="col-12 col-md-3">
        <h2>Blog</h2>
        <p class="text-muted">View and update blog settings</p>
      </div>

      <div class="col-12 col-md-9">
        <div class="card">
          <div class="card-body">
            <div class="mb-4">
              <label class="form-check">
                <input type="hidden" name="blog_schema_enabled" value="0">
                <input type="checkbox" name="blog_schema_enabled" value="1" class="form-check-input" @checked(old('blog_schema_enabled', $settings['blog_schema_enabled'])) data-blog-schema-toggle>
                <span class="form-check-label">Enable Schema for blog posts</span>
              </label>
              <div class="form-hint ms-4">Learn more: <a href="https://schema.org/Article" target="_blank" rel="noopener">https://schema.org/Article</a></div>
            </div>

            <div class="border rounded p-4 mb-4 {{ ! old('blog_schema_enabled', $settings['blog_schema_enabled']) ? 'd-none' : '' }}" data-blog-schema-panel>
              <label class="form-label" for="blog-schema-type">Schema type</label>
              <select name="blog_schema_type" id="blog-schema-type" class="form-select">
                @foreach ($schemaTypeOptions as $value => $label)
                  <option value="{{ $value }}" @selected(old('blog_schema_type', $settings['blog_schema_type']) === $value)>{{ $label }}</option>
                @endforeach
              </select>
            </div>

            <div class="mb-0">
              <label class="form-check">
                <input type="hidden" name="blog_anchor_links_enabled" value="0">
                <input type="checkbox" name="blog_anchor_links_enabled" value="1" class="form-check-input" @checked(old('blog_anchor_links_enabled', $settings['blog_anchor_links_enabled']))>
                <span class="form-check-label">Add anchor links to post headings</span>
              </label>
              <div class="form-hint ms-4">Gives each h2 and h3 in a post body an id, so sections can be linked and cited directly (e.g. /my-post#pricing). Headings you already gave an id keep it, and nothing inside code samples is changed. Only affects what visitors see - your stored content is untouched.</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </form>

  <div class="row mb-5 d-block d-md-flex">
    <div class="col-12 col-md-3"></div>
    <div class="col-12 col-md-9">
      <button type="submit" class="btn btn-primary btn-lg" form="blog-settings-form">
        @include('core/base::admin.partials.icon', ['name' => 'save'])
        <span class="ms-2">Save settings</span>
      </button>
    </div>
  </div>
@endsection

@push('scripts')
  <script>
    ;(function () {
      var toggle = document.querySelector('[data-blog-schema-toggle]')
      var panel = document.querySelector('[data-blog-schema-panel]')

      function syncPanel() {
        if (toggle && panel) {
          panel.classList.toggle('d-none', !toggle.checked)
        }
      }

      toggle && toggle.addEventListener('change', syncPanel)
      syncPanel()
    })()
  </script>
@endpush
