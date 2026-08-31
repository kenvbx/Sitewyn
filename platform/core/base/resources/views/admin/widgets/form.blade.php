@php
    $isEdit = isset($widget) && $widget->exists;
    $currentType = old('type', $widget->type ?? \Sitewyn\Core\Base\Models\Widget::TYPE_PAGES);
    $currentData = old('data', $widget->data ?? []);
    // Nested data.* keys: the error bag stores them dot-style, not
    // bracket-style, so the invalid state is resolved by hand here.
    $titleError = $errors->has('data.title');
    $limitError = $errors->has('data.limit');
    $contentError = $errors->has('data.content');
@endphp

<form
    method="POST"
    action="{{ $isEdit ? route('admin.widgets.update', $widget, false) : route('admin.widgets.store', [], false) }}"
    class="needs-validation"
    data-admin-validate
    novalidate
>
  @csrf
  @if ($isEdit)
    @method('PUT')
  @endif

  <input type="hidden" name="area_slug" value="{{ $areaSlug }}">

  <x-admin-form-group
    name="type"
    label="Type"
    type="select"
    :options="$types"
    :value="$currentType"
    hint="The fields below adapt to the chosen type."
    data-widget-type-select
  />

  <div class="mb-3">
    <label class="form-label" for="widget-title">Heading</label>
    <input
      type="text"
      id="widget-title"
      name="data[title]"
      value="{{ old('data.title', $currentData['title'] ?? '') }}"
      maxlength="191"
      autocomplete="off"
      class="form-control {{ $titleError ? 'is-invalid' : '' }}"
    >
    <div class="form-hint">Optional heading shown above the widget on the frontend. Leave empty for no heading.</div>
    @if ($titleError)
      <div class="invalid-feedback">{{ $errors->first('data.title') }}</div>
    @endif
  </div>

  <div data-widget-fields="{{ \Sitewyn\Core\Base\Models\Widget::TYPE_RECENT_POSTS }}" class="{{ $currentType === \Sitewyn\Core\Base\Models\Widget::TYPE_RECENT_POSTS ? '' : 'd-none' }}">
    <div class="mb-3">
      <label class="form-label" for="widget-limit">Number of posts</label>
      <input
        type="number"
        id="widget-limit"
        name="data[limit]"
        value="{{ old('data.limit', $currentData['limit'] ?? 5) }}"
        min="1"
        max="20"
        class="form-control {{ $limitError ? 'is-invalid' : '' }}"
      >
      <div class="form-hint">How many of the latest published posts to show (1–20).</div>
      @if ($limitError)
        <div class="invalid-feedback">{{ $errors->first('data.limit') }}</div>
      @endif
    </div>
  </div>

  <div data-widget-fields="{{ \Sitewyn\Core\Base\Models\Widget::TYPE_TEXT }}" class="{{ $currentType === \Sitewyn\Core\Base\Models\Widget::TYPE_TEXT ? '' : 'd-none' }}">
    <x-admin-editor
      name="data[content]"
      :value="$currentData['content'] ?? ''"
      label="Content"
      :height="240"
      hint="Free-form rich text; it renders inside the widget area like page content."
    />
    @if ($contentError)
      <div class="invalid-feedback d-block">{{ $errors->first('data.content') }}</div>
    @endif
  </div>

  <div class="text-end">
    <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Save changes' : 'Create widget' }}</button>
    <a href="{{ route('admin.widgets.index', ['area' => $areaSlug]) }}" class="btn btn-link">Cancel</a>
  </div>
</form>

@once
  @push('scripts')
    <script>
      // Pure JS type toggle: show only the field groups that belong to the
      // selected widget type.
      document.querySelectorAll('[data-widget-type-select]').forEach((select) => {
        const form = select.closest('form');
        const groups = form ? form.querySelectorAll('[data-widget-fields]') : [];

        const sync = () => {
          groups.forEach((group) => {
            group.classList.toggle('d-none', group.getAttribute('data-widget-fields') !== select.value);
          });
        };

        select.addEventListener('change', sync);
        sync();
      });
    </script>
  @endpush
@endonce
