<?php

namespace Sitewyn\Core\Base\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\View\Component;
use Sitewyn\Core\Base\Support\WidgetRenderer;

/**
 * Frontend widget area (P5-04): renders every widget assigned to the slug
 * in the admin. Presentation belongs to the active theme — each widget is
 * included as the top-level `widgets.{type}` view, which the view finder
 * resolves into the theme first. A type the theme ships no partial for is
 * skipped silently; an area with nothing to show renders nothing at all,
 * so layouts without widgets keep their original markup.
 */
class WidgetArea extends Component
{
    public function __construct(public string $slug) {}

    public function render(): View
    {
        $widgets = app(WidgetRenderer::class)
            ->resolveWidgets($this->slug)
            ->filter(fn (array $entry): bool => ViewFacade::exists('widgets.'.$entry['widget']->type))
            ->values();

        return view('core/base::components.widget-area', [
            'widgets' => $widgets,
        ]);
    }
}
