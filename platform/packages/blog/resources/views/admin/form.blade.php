<div class="row row-cards">
  <div class="col-lg-8">
    <x-admin-card title="Post content">
      <x-admin-form-group
        name="title"
        label="Title"
        :value="$post->title"
        required
        autocomplete="off"
        :maxlength="255"
        placeholder="How to build a module"
        invalid-feedback="Title is required."
      />
      <x-admin-editor
        name="content"
        :value="$post->content"
        label="Content"
        :height="480"
        hint="Use the editor toolbar to format the post; the Image button opens the media picker."
      />

      <x-slot:footer>
        <div class="text-end">
          <button type="submit" class="btn btn-primary">Save post</button>
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
        :value="$post->status ?? 'draft'"
        :options="['draft' => 'Draft', 'published' => 'Published']"
      />
      <x-admin-form-group
        name="slug"
        label="Slug"
        :value="$post->slug"
        autocomplete="off"
        :maxlength="255"
        placeholder="Leave blank to generate from the title"
        :hint="$post->exists ? 'Leave blank to keep the current slug.' : 'Leave blank to generate a slug from the title.'"
      />
    </x-admin-card>
    <x-admin-card title="Featured image" class="mb-3" data-post-featured>
      <input
        type="hidden"
        name="featured_image"
        id="featured_image"
        value="{{ old('featured_image', $post->featured_image) }}"
        data-post-featured-input
      />
      <div class="border rounded p-2 text-center mb-3 bg-secondary-lt bg-opacity-25" data-post-featured-preview>
        @if ($featuredImage = old('featured_image', $post->featured_image))
          <img src="{{ $featuredImage }}" class="img-fluid rounded" alt="Featured image preview">
        @else
          <span class="text-secondary">No image selected.</span>
        @endif
      </div>
      <div class="btn-list">
        @can('media.index')
          <button type="button" class="btn" data-post-featured-choose>
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
              <path d="M15 8h.01" />
              <path d="M3 6a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v12a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3v-12z" />
              <path d="M3 16l5 -5c.928 -.893 2.072 -.893 3 0l5 5" />
              <path d="M14 14l1 -1c.928 -.893 2.072 -.893 3 0l3 3" />
            </svg>
            Choose image
          </button>
        @endcan
        <button type="button" class="btn btn-outline-danger" data-post-featured-clear>Clear</button>
      </div>
      <div class="form-hint">
        The featured image URL is stored on the post and used as its thumbnail.
      </div>
    </x-admin-card>
    <x-admin-card title="Taxonomy" class="mb-3">
      <x-admin-form-group
        name="category_id"
        label="Category"
        type="select"
        :value="$post->category_id"
        :options="['' => '— None —'] + $categories->mapWithKeys(fn ($category) => [(string) $category->id => $category->name])->all()"
        hint="Posts without a category appear as uncategorized."
      />
      <x-admin-form-group
        name="tags_input"
        label="Tags"
        :value="$post->exists ? $post->tags->pluck('name')->implode(', ') : ''"
        autocomplete="off"
        :maxlength="2000"
        placeholder="Laravel, PHP"
        hint="Comma-separated tag names; missing tags are created automatically."
      />
    </x-admin-card>
    @include('core/base::admin.partials.seo-fields', [
      'seoTitle' => old('seo_title', $post->seo_title),
      'seoDescription' => old('seo_description', $post->seo_description),
      'ogImage' => old('og_image', $post->og_image),
      'showOgCopy' => true,
    ])
  </div>
</div>

@once
  @push('scripts')
    <script>
      ;(function () {
        var block = document.querySelector('[data-post-featured]')

        if (! block) return

        var input = block.querySelector('[data-post-featured-input]')
        var preview = block.querySelector('[data-post-featured-preview]')
        var choose = block.querySelector('[data-post-featured-choose]')
        var clear = block.querySelector('[data-post-featured-clear]')

        function render(url) {
          input.value = url || ''

          if (url) {
            var image = document.createElement('img')

            image.src = url
            image.alt = 'Featured image preview'
            image.className = 'img-fluid rounded'
            preview.innerHTML = ''
            preview.appendChild(image)
          } else {
            preview.innerHTML = '<span class="text-secondary">No image selected.</span>'
          }
        }

        if (choose) {
          // Reuses the admin:editor-file-picker bridge documented on the
          // media picker: the picker opens its modal and calls the callback
          // with the chosen URL once a file is selected.
          choose.addEventListener('click', function () {
            document.dispatchEvent(new CustomEvent('admin:editor-file-picker', {
              detail: {
                callback: render,
                filetype: 'image',
                handled: false,
              },
            }))
          })
        }

        if (clear) {
          clear.addEventListener('click', function () {
            render('')
          })
        }
      })()
    </script>
  @endpush
@endonce
