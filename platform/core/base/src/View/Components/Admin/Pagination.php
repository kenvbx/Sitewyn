<?php

namespace Sitewyn\Core\Base\View\Components\Admin;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Pagination extends Component
{
    public function __construct(
        public Paginator $paginator,
        public bool $cardFooter = true,
        public string $class = 'd-flex align-items-center',
    ) {}

    public function render(): View
    {
        return view('core/base::components.admin.pagination');
    }
}
