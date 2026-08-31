{{--
    Shared frontend layout of the Sitewyn default theme (P5-02).

    The active theme owns the public views: controllers render the top-level
    names frontend.page / frontend.post, which resolve into the theme's
    resources/views first (BaseServiceProvider prepends the location). Page
    and post views @extend this layout and fill the title, meta and content
    sections.
--}}
<!doctype html>
<html lang="{{ $translation?->locale ?? str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>@yield('title')</title>
  @stack('meta')
  @vite(['platform/themes/default/resources/css/theme.css'])
</head>
<body>
  @php
    // The default theme ships with the CMS, so reaching into the page and
    // blog modules here is an accepted coupling — custom themes may render
    // any navigation they like. A menu assigned to the primary location
    // (P5-03) replaces the automatic published-pages nav. Menu items store
    // page/post target ids, so slugs are resolved at render time and dead
    // targets (deleted or unpublished) simply drop out of the nav. An empty
    // or missing menu falls back to the pages nav the theme always shipped.
    $navPages = app(\Sitewyn\Packages\Page\Repositories\PageRepository::class)
        ->byStatus(\Sitewyn\Packages\Page\Models\Page::STATUS_PUBLISHED);
    $siteName = site_setting('site_name', config('app.name', 'Sitewyn'));

    $primaryMenu = \Sitewyn\Core\Base\Models\Menu::forLocation(\Sitewyn\Core\Base\Models\Menu::LOCATION_PRIMARY);
    $navLinks = [];

    if ($primaryMenu !== null && $primaryMenu->items->isNotEmpty()) {
        // Batch-resolve page/post slugs once instead of one query per item.
        $allItems = $primaryMenu->items->flatMap(fn ($item) => collect([$item])->concat($item->children));
        $pageSlugs = collect();
        $postSlugs = collect();

        $pageIds = $allItems->where('type', \Sitewyn\Core\Base\Models\MenuItem::TYPE_PAGE)->pluck('target_id')->filter()->unique();
        $postIds = $allItems->where('type', \Sitewyn\Core\Base\Models\MenuItem::TYPE_POST)->pluck('target_id')->filter()->unique();

        if ($pageIds->isNotEmpty()) {
            $pageSlugs = \Sitewyn\Packages\Page\Models\Page::query()
                ->whereIn('id', $pageIds)
                ->where('status', \Sitewyn\Packages\Page\Models\Page::STATUS_PUBLISHED)
                ->pluck('slug', 'id');
        }

        if ($postIds->isNotEmpty()) {
            $postSlugs = \Sitewyn\Packages\Blog\Models\Post::query()
                ->whereIn('id', $postIds)
                ->where('status', \Sitewyn\Packages\Blog\Models\Post::STATUS_PUBLISHED)
                ->pluck('slug', 'id');
        }

        $hrefFor = fn ($item) => match ($item->type) {
            \Sitewyn\Core\Base\Models\MenuItem::TYPE_PAGE => $pageSlugs->get($item->target_id) ? '/'.$pageSlugs->get($item->target_id) : null,
            \Sitewyn\Core\Base\Models\MenuItem::TYPE_POST => $postSlugs->get($item->target_id) ? '/blog/'.$postSlugs->get($item->target_id) : null,
            default => trim((string) $item->url) !== '' ? $item->url : null,
        };

        $isExternal = fn (string $href): bool => str_starts_with($href, 'http://') || str_starts_with($href, 'https://');

        foreach ($primaryMenu->items as $item) {
            $href = $hrefFor($item);

            if ($href === null) {
                continue;
            }

            $children = [];

            foreach ($item->children as $child) {
                $childHref = $hrefFor($child);

                if ($childHref === null) {
                    continue;
                }

                $children[] = [
                    'label' => $child->label,
                    'href' => $childHref,
                    'external' => $isExternal($childHref),
                ];
            }

            $navLinks[] = [
                'label' => $item->label,
                'href' => $href,
                'external' => $isExternal($href),
                'children' => $children,
            ];
        }
    }
  @endphp
  <header class="site-header">
    <div class="site-header-inner">
      <p class="site-title"><a href="/">{{ $siteName }}</a></p>
      @if ($navLinks !== [])
        <nav class="site-nav" aria-label="Primary">
          @foreach ($navLinks as $link)
            <a href="{{ $link['href'] }}"{!! $link['external'] ? ' target="_blank" rel="noopener"' : '' !!}>{{ $link['label'] }}</a>@if ($link['children'] !== [])
              <ul class="site-nav-children">
                @foreach ($link['children'] as $child)
                  <li><a href="{{ $child['href'] }}"{!! $child['external'] ? ' target="_blank" rel="noopener"' : '' !!}>{{ $child['label'] }}</a></li>
                @endforeach
              </ul>
            @endif
          @endforeach
        </nav>
      @elseif ($navPages->isNotEmpty())
        <nav class="site-nav" aria-label="Primary">
          @foreach ($navPages as $navPage)
            <a href="/{{ $navPage->slug }}">{{ $navPage->title }}</a>
          @endforeach
        </nav>
      @endif
    </div>
  </header>
  <main class="site-main">
    @yield('content')
  </main>
  <footer class="site-footer">
    <div class="site-footer-inner">
      {{-- Widget areas (P5-04): the theme.json declares them, the admin
           fills them. An empty area renders nothing, so the footer keeps
           its original markup until a widget is assigned. --}}
      <x-widget-area slug="footer" />
      <p class="site-footer-copy">&copy; {{ date('Y') }} {{ $siteName }}</p>
      <p class="site-footer-credit">Proudly powered by <a href="https://sitewyn.dev" rel="generator">Sitewyn</a></p>
    </div>
  </footer>
</body>
</html>
