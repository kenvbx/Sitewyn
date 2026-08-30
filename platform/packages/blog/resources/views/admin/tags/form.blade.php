<div class="row row-cards">
  <div class="col-lg-8">
    <x-admin-card title="Tag details">
      <x-admin-form-group
        name="name"
        label="Name"
        :value="$tag->name"
        required
        autocomplete="off"
        :maxlength="255"
        placeholder="Laravel"
        invalid-feedback="Name is required."
      />
      <x-admin-form-group
        name="slug"
        label="Slug"
        :value="$tag->slug"
        autocomplete="off"
        :maxlength="255"
        placeholder="Leave blank to generate from the name"
        :hint="$tag->exists ? 'Leave blank to keep the current slug.' : 'Leave blank to generate a slug from the name.'"
      />

      <x-slot:footer>
        <div class="text-end">
          <button type="submit" class="btn btn-primary">Save tag</button>
        </div>
      </x-slot:footer>
    </x-admin-card>
  </div>
</div>
