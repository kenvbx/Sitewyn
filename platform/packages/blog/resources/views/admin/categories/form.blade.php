<div class="row row-cards">
  <div class="col-lg-8">
    <x-admin-card title="Category content">
      <x-admin-form-group
        name="name"
        label="Name"
        :value="$category->name"
        required
        autocomplete="off"
        :maxlength="255"
        placeholder="Technology"
        invalid-feedback="Name is required."
      />

      <x-slot:footer>
        <div class="text-end">
          <button type="submit" class="btn btn-primary">Save category</button>
        </div>
      </x-slot:footer>
    </x-admin-card>
  </div>
  <div class="col-lg-4">
    <x-admin-card title="Organization" class="mb-3">
      <x-admin-form-group
        name="parent_id"
        label="Parent"
        type="select"
        :value="$category->parent_id"
        :options="$parents"
        hint="Leave as — None — for a top-level category. When editing, the category itself and its subtree are not selectable."
      />
      <x-admin-form-group
        name="slug"
        label="Slug"
        :value="$category->slug"
        autocomplete="off"
        :maxlength="255"
        placeholder="Leave blank to generate from the name"
        :hint="$category->exists ? 'Leave blank to keep the current slug.' : 'Leave blank to generate a slug from the name.'"
      />
    </x-admin-card>
    <x-admin-card title="Description" class="mb-3">
      <x-admin-form-group
        name="description"
        label="Description"
        type="textarea"
        :value="$category->description"
        :rows="3"
        placeholder="Short description for the category archive"
      />
    </x-admin-card>
  </div>
</div>

{{-- The Translations section is part of the same <form>; every language card submits with the category. --}}
@include('package/blog::admin.categories.translations')
