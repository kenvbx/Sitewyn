<?php

namespace Sitewyn\Core\Base\View\Components\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\View\Component;

class FormGroup extends Component
{
    public string $fieldId;

    public function __construct(
        public string $name,
        public ?string $label = null,
        public string $type = 'text',
        public mixed $value = null,
        public array $options = [],
        public ?string $id = null,
        public ?string $hint = null,
        public ?string $placeholder = null,
        public ?string $autocomplete = null,
        public ?string $invalidFeedback = null,
        public ?string $pattern = null,
        public ?int $maxlength = null,
        public ?int $minlength = null,
        public int $rows = 4,
        public bool $required = false,
        public bool $multiple = false,
    ) {
        $this->fieldId = $id ?: Str::of($name)->replace(['[', ']'], ['-', ''])->trim('-')->toString();
    }

    public function render(): View
    {
        return view('core/base::components.admin.form-group');
    }
}
