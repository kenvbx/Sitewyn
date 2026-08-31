@php
    $isEdit = isset($menu) && $menu->exists;
@endphp

<form
    method="POST"
    action="{{ $isEdit ? route('admin.menus.update', $menu, false) : route('admin.menus.store', [], false) }}"
    class="needs-validation"
    data-admin-validate
    novalidate
>
  @csrf
  @if ($isEdit)
    @method('PUT')
  @endif

  <x-admin-form-group
    name="name"
    label="Name"
    :value="old('name', $menu->name ?? '')"
    required
    autocomplete="off"
    :maxlength="191"
    placeholder="Main navigation"
    invalid-feedback="Name is required."
  />
  <x-admin-form-group
    name="slug"
    label="Slug"
    :value="old('slug', $menu->slug ?? '')"
    autocomplete="off"
    :maxlength="191"
    placeholder="main-navigation"
    hint="Leave empty to generate one from the name. Duplicated slugs get a -2, -3, ... suffix instead of an error."
    invalid-feedback="Use letters, numbers, and dashes only."
  />
  <x-admin-form-group
    name="location"
    label="Location"
    type="select"
    :options="['' => '— No location (draft) —', 'primary' => 'Primary (header nav)']"
    :value="old('location', $menu->location ?? '')"
    hint="A location can only be claimed by one menu at a time — assigning it here releases it from the previous menu."
  />

  <div class="text-end">
    <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Save changes' : 'Create menu' }}</button>
    <a href="{{ route('admin.menus.index') }}" class="btn btn-link">Cancel</a>
  </div>
</form>
