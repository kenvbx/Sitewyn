<?php

namespace Sitewyn\Core\Base\View\Components\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Card extends Component
{
    public function __construct(
        public ?string $title = null,
        public ?string $subtitle = null,
        public string $class = '',
        public string $headerClass = '',
        public string $bodyClass = '',
        public string $footerClass = '',
        public bool $body = true,
    ) {}

    public function render(): View
    {
        return view('core/base::components.admin.card');
    }
}
