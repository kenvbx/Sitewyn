<?php

namespace Sitewyn\Packages\Media\View\Components\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\View\Component;

class MediaPicker extends Component
{
    public string $fieldId;

    public string $modalId;

    public string $rootId;

    public string $urlFieldId;

    public string $previewId;

    public function __construct(
        public string $name,
        public ?string $label = null,
        public mixed $value = null,
        public ?string $urlName = null,
        public ?string $urlValue = null,
        public string $buttonLabel = 'Choose media',
        public string $modalTitle = 'Select media',
        public bool $inline = true,
        public bool $required = false,
        public ?string $formId = null,
    ) {
        $baseId = Str::slug(str_replace(['[', ']'], ['-', ''], $this->name)) ?: 'media';

        $this->rootId = $baseId.'-media-picker';
        $this->fieldId = $baseId;
        $this->modalId = $baseId.'-media-picker-modal';
        $this->urlFieldId = $baseId.'-url';
        $this->previewId = $baseId.'-preview';
        $this->urlName ??= $this->name.'_url';
    }

    public function render(): View
    {
        return view('package/media::components.admin.media-picker');
    }
}
