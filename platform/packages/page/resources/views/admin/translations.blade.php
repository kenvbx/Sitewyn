@php
    /** @var \Sitewyn\Packages\Page\Models\Page $page */
    /** @var \Illuminate\Support\Collection<int, \Sitewyn\Core\Base\Models\Language> $languages */
    // Section "Translations" of the page form (P5-01): one card per active
    // non-default language. Inputs submit as translations[<locale>][<field>].
    // Empty fields fall back to the default language — placeholders show the
    // default content so translators know what they are translating.
    $existing = $page->exists ? $page->translations->keyBy('locale') : collect();
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
      <div class="mb-3">
        <label class="form-label" for="translations-{{ $locale }}-title">Title</label>
        <input
          type="text"
          id="translations-{{ $locale }}-title"
          name="translations[{{ $locale }}][title]"
          value="{{ old('translations.'.$locale.'.title', $translation?->title) }}"
          placeholder="{{ $page->title }}"
          maxlength="255"
          autocomplete="off"
          class="form-control @error('translations.'.$locale.'.title') is-invalid @enderror"
        />
        @error('translations.'.$locale.'.title')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>

      <x-admin-editor
        name="translations[{{ $locale }}][content]"
        :value="old('translations.'.$locale.'.content', $translation?->content)"
        label="Content"
        :height="360"
        :placeholder="$page->content !== null ? \Illuminate\Support\Str::limit(trim(strip_tags($page->content)), 120) : null"
        hint="Empty content falls back to the default language."
      />

      <div class="mb-3">
        <label class="form-label" for="translations-{{ $locale }}-seo-title">SEO title</label>
        <input
          type="text"
          id="translations-{{ $locale }}-seo-title"
          name="translations[{{ $locale }}][seo_title]"
          value="{{ old('translations.'.$locale.'.seo_title', $translation?->seo_title) }}"
          placeholder="{{ $page->seo_title }}"
          maxlength="255"
          autocomplete="off"
          class="form-control @error('translations.'.$locale.'.seo_title') is-invalid @enderror"
        />
        @error('translations.'.$locale.'.seo_title')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>

      <div class="mb-0">
        <label class="form-label" for="translations-{{ $locale }}-seo-description">SEO description</label>
        <textarea
          id="translations-{{ $locale }}-seo-description"
          name="translations[{{ $locale }}][seo_description]"
          rows="2"
          maxlength="500"
          placeholder="{{ $page->seo_description }}"
          class="form-control @error('translations.'.$locale.'.seo_description') is-invalid @enderror"
        >{{ old('translations.'.$locale.'.seo_description', $translation?->seo_description) }}</textarea>
        @error('translations.'.$locale.'.seo_description')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>
    </x-admin-card>
  @endforeach
@endif
