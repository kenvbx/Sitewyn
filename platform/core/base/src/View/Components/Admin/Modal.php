<?php

namespace Sitewyn\Core\Base\View\Components\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Modal extends Component
{
    public function __construct(
        public string $id,
        public ?string $title = null,
        public ?string $size = null,
        public bool $centered = true,
        public bool $scrollable = false,
        public bool $staticBackdrop = false,
    ) {}

    public function render(): View
    {
        return view('core/base::components.admin.modal');
    }
}
