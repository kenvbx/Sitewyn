@php
    /** @var \Sitewyn\Packages\Blog\Models\Category $category */
    /** @var \Illuminate\Support\Collection<int, \Sitewyn\Core\Base\Models\Language> $languages */
    // Section "Translations" of the category form (P5-01): one card per
    // active non-default language, name field only. Inputs submit as
    // translations[<locale>][name]; an empty name falls back to the default.
    $existing = $category->exists ? $category->translations->keyBy('locale') : collect();
@endphp

<h2 class="h3 mt-4 mb-3">Translations</h2>

@if ($languages->isEmpty())
  <p class="text-secondary">
    Add languages in Settings to translate content.
    <a href="{{ route('admin.settings.languages.index') }}">Manage languages</a>
  </p>
@else
  @foreach ($languages as $language)
    @php
        $locale = $language->code;
        $translation = $existing->get($locale);
    @endphp
    <x-admin-card
      title="{{ $language->name }} ({{ \Illuminate\Support\Str::upper($locale) }})"
      subtitle="Empty fields fall back to the default language."
      class="mb-3"
    >
      <div class="mb-0">
        <label class="form-label" for="translations-{{ $locale }}-name">Name</label>
        <input
          type="text"
          id="translations-{{ $locale }}-name"
          name="translations[{{ $locale }}][name]"
          value="{{ old('translations.'.$locale.'.name', $translation?->name) }}"
          placeholder="{{ $category->name }}"
          maxlength="255"
          autocomplete="off"
          class="form-control @error('translations.'.$locale.'.name') is-invalid @enderror"
        />
        @error('translations.'.$locale.'.name')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>
    </x-admin-card>
  @endforeach
@endif
