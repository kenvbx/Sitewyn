@php
    // Shared "SEO" card for the page and post admin forms (P3-09). Values
    // arrive resolved with old() from the including form; showOgCopy renders
    // the post-only button that copies the featured image URL into og:image.
    $showOgCopy = $showOgCopy ?? false;
@endphp

<x-admin-card title="SEO">
  <x-admin-form-group
    name="seo_title"
    label="SEO title"
    :value="$seoTitle"
    autocomplete="off"
    :maxlength="255"
    placeholder="Defaults to the title"
    data-seo-counter="60"
  />
  <x-admin-form-group
    name="seo_description"
    label="SEO description"
    type="textarea"
    :value="$seoDescription"
    :rows="3"
    :maxlength="500"
    placeholder="Short description for search engines"
    data-seo-counter="160"
  />
  {{-- Plain text input on purpose (like featured_image): the media picker
       returns relative URLs such as /storage/..., which <input type="url">
       would reject at submit time via checkValidity(). --}}
  <x-admin-form-group
    name="og_image"
    label="og:image URL"
    :value="$ogImage"
    autocomplete="off"
    :maxlength="255"
    placeholder="https://example.com/social-card.jpg"
    hint="Open Graph image URL shown when the page is shared on social media."
  />
  @if ($showOgCopy)
    <div class="text-end">
      <button type="button" class="btn btn-sm" data-seo-og-copy>Use featured image</button>
    </div>
  @endif
</x-admin-card>

@once
  @push('scripts')
    <script>
      ;(function () {
        // Character counters: show "N/limit" under each counted SEO field and
        // turn red past the Google display limit (60 title / 160 description).
        // Advisory only — it never blocks submit, the 255/500 maxlengths and
        // the server rules still cap the stored values.
        document.querySelectorAll('[data-seo-counter]').forEach(function (field) {
          var limit = parseInt(field.getAttribute('data-seo-counter'), 10)
          var group = field.closest('.mb-3')

          if (! group || ! limit) return

          var counter = document.createElement('div')

          counter.className = 'form-hint'
          group.appendChild(counter)

          function update() {
            var length = Array.from(field.value).length

            counter.textContent = length + '/' + limit
            counter.classList.toggle('text-danger', length > limit)
          }

          field.addEventListener('input', update)
          update()
        })

        // Post form only: copy the featured image URL into the og:image field.
        var copy = document.querySelector('[data-seo-og-copy]')

        if (copy) {
          copy.addEventListener('click', function () {
            var featured = document.querySelector('#featured_image')
            var og = document.querySelector('#og_image')

            if (featured && og && featured.value) {
              og.value = featured.value
            }
          })
        }
      })()
    </script>
  @endpush
@endonce
