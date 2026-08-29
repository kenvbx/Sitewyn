<?php

namespace Sitewyn\Core\Base\View\Components\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Toast extends Component
{
    public function __construct(
        public string $id,
        public ?string $title = null,
        public string $type = 'info',
        public ?string $time = null,
        public bool $autohide = false,
        public bool $show = true,
    ) {}

    public function render(): View
    {
        return view('core/base::components.admin.toast');
    }
}
