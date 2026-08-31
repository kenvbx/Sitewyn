<div class="row row-cards">
  <div class="col-lg-8">
    <x-admin-card title="Page content">
      <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item" role="presentation">
          <button type="button" class="nav-link active" id="page-content-tab" data-bs-toggle="tab" data-bs-target="#page-content-pane" role="tab" aria-controls="page-content-pane" aria-selected="true">
            Content
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button type="button" class="nav-link" id="page-seo-tab" data-bs-toggle="tab" data-bs-target="#page-seo-pane" role="tab" aria-controls="page-seo-pane" aria-selected="false">
            SEO
          </button>
        </li>
      </ul>
      <div class="tab-content">
        <div class="tab-pane fade show active" id="page-content-pane" role="tabpanel" aria-labelledby="page-content-tab" tabindex="0">
          <x-admin-form-group
            name="title"
            label="Title"
            :value="$page->title"
            required
            autocomplete="off"
            :maxlength="255"
            placeholder="About us"
            invalid-feedback="Title is required."
          />
          <x-admin-form-group
            name="slug"
            label="Slug"
            :value="$page->slug"
            autocomplete="off"
            :maxlength="255"
            placeholder="Leave blank to generate from the title"
            :hint="$page->exists ? 'Leave blank to keep the current slug.' : 'Leave blank to generate a slug from the title.'"
          />
          <x-admin-form-group
            name="short_description"
            label="Short description"
            type="textarea"
            :value="$page->short_description"
            :rows="3"
            :maxlength="500"
            hint="A brief summary used by themes and listings."
          />
          <x-admin-editor
            name="content"
            :value="$page->content"
            label="Content"
            :height="480"
            hint="Use the editor toolbar to format the page; the Image button opens the media picker."
          />
        </div>
        <div class="tab-pane fade" id="page-seo-pane" role="tabpanel" aria-labelledby="page-seo-tab" tabindex="0">
          @include('core/base::admin.partials.seo-fields', [
            'seoTitle' => old('seo_title', $page->seo_title),
            'seoDescription' => old('seo_description', $page->seo_description),
            'ogImage' => old('og_image', $page->og_image),
          ])
        </div>
      </div>

      <x-slot:footer>
        <div class="text-end">
          <button type="submit" class="btn btn-primary">Save page</button>
        </div>
      </x-slot:footer>
    </x-admin-card>
  </div>
  <div class="col-lg-4">
    <x-admin-card title="Publishing" class="mb-3">
      <x-admin-form-group
        name="status"
        label="Status"
        type="select"
        :value="$page->status ?? 'draft'"
        :options="['draft' => 'Draft', 'published' => 'Published']"
      />
    </x-admin-card>
  </div>
</div>

{{-- The Translations section is part of the same <form>; every language card submits with the page. --}}
@include('package/page::admin.translations')
