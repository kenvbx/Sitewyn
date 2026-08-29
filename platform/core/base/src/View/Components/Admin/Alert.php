<?php

namespace Sitewyn\Core\Base\View\Components\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Alert extends Component
{
    public function __construct(
        public string $type = 'info',
        public ?string $title = null,
        public bool $dismissible = false,
        public bool $important = false,
    ) {}

    public function render(): View
    {
        return view('core/base::components.admin.alert');
    }
}
