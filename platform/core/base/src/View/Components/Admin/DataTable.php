<?php

namespace Sitewyn\Core\Base\View\Components\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\View\Component;

class DataTable extends Component
{
    public string $tableId;

    public function __construct(
        public ?string $title = null,
        public ?string $subtitle = null,
        public ?string $id = null,
        public string $empty = 'No records found.',
        public int $emptyColspan = 1,
        public string $class = '',
        public string $tableClass = 'table table-vcenter card-table table-striped',
        public string $responsiveClass = 'table-responsive',
        public array $valueNames = [],
        public bool $searchable = false,
        public bool $paginated = false,
        public int $page = 10,
        public string $searchPlaceholder = 'Search...',
    ) {}

    public function render(): View
    {
        $this->tableId = $this->id ?: 'table-'.Str::random(8);

        return view('core/base::components.admin.data-table');
    }
}
