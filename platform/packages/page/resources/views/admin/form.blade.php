<div class="row row-cards">
  <div class="col-lg-8">
    <x-admin-card title="Page content">
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
      <x-admin-editor
        name="content"
        :value="$page->content"
        label="Content"
        :height="480"
        hint="Use the editor toolbar to format the page; the Image button opens the media picker."
      />

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
      <x-admin-form-group
        name="slug"
        label="Slug"
        :value="$page->slug"
        autocomplete="off"
        :maxlength="255"
        placeholder="Leave blank to generate from the title"
        :hint="$page->exists ? 'Leave blank to keep the current slug.' : 'Leave blank to generate a slug from the title.'"
      />
    </x-admin-card>
    @include('core/base::admin.partials.seo-fields', [
      'seoTitle' => old('seo_title', $page->seo_title),
      'seoDescription' => old('seo_description', $page->seo_description),
      'ogImage' => old('og_image', $page->og_image),
    ])
  </div>
</div>

