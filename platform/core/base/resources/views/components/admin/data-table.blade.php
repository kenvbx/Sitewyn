<div id="{{ $tableId }}">
  <x-admin-card :title="$title" :subtitle="$subtitle" :class="$class" :body="false">
    @if ($searchable)
      <x-slot:actions>
        <div class="input-icon">
          <span class="input-icon-addon">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
              <path stroke="none" d="M0 0h24v24H0z" fill="none" />
              <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" />
              <path d="M21 21l-6 -6" />
            </svg>
          </span>
          <input type="text" value="" class="form-control search" placeholder="{{ $searchPlaceholder }}" aria-label="{{ $searchPlaceholder }}" />
        </div>
      </x-slot:actions>
    @endif

    <div class="{{ $responsiveClass }}">
      <table {{ $attributes->merge(['class' => $tableClass]) }}>
        @isset($head)
          <thead>
            {{ $head }}
          </thead>
        @endisset
        <tbody class="{{ $valueNames ? 'table-tbody' : '' }}">
          @if (trim((string) $slot) !== '')
            {{ $slot }}
          @else
            <tr>
              <td colspan="{{ $emptyColspan }}" class="text-center text-secondary py-5">{{ $empty }}</td>
            </tr>
          @endif
        </tbody>
      </table>
    </div>
    @if ($paginated)
      <x-slot:footer>
        <div class="d-flex align-items-center">
          <ul class="pagination m-0 ms-auto"></ul>
        </div>
      </x-slot:footer>
    @elseif (isset($footer))
      <x-slot:footer>
        {{ $footer }}
      </x-slot:footer>
    @endif
  </x-admin-card>
</div>

@if ($valueNames)
  @pushOnce('scripts', 'tabler-list-js')
    <script src="{{ asset('vendor/tabler/dist/libs/list.js/dist/list.min.js') }}"></script>
  @endPushOnce

  @push('scripts')
    <script>
      ;(function () {
        var listId = @js($tableId);
        var valueNames = @js($valueNames);

        function initTablerList(attempt) {
          if (typeof List === 'undefined') {
            if ((attempt || 0) < 20) {
              window.setTimeout(function () {
                initTablerList((attempt || 0) + 1)
              }, 50)
            }

            return
          }

          window.tabler_list = window.tabler_list || {}
          window.tabler_list[listId] = new List(listId, {
            sortClass: 'table-sort',
            listClass: 'table-tbody',
            valueNames,
            page: {{ $paginated ? $page : 'undefined' }},
            pagination: {{ $paginated ? 'true' : 'false' }},
          })

          var searchInput = document.querySelector('#' + listId + ' .search')
          if (searchInput) {
            searchInput.addEventListener('input', function () {
              window.tabler_list[listId].search(this.value)
            })
          }
        }

        document.readyState !== 'loading' ? initTablerList(0) : document.addEventListener('DOMContentLoaded', function () {
          initTablerList(0)
        }, { once: true })
      })()
    </script>
  @endpush
@endif
