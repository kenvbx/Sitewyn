<?php

namespace Sitewyn\Core\Base\View\Components\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\View\Component;

/**
 * Rich text editor field backed by TinyMCE, shared by admin content forms.
 *
 * The textarea is hidden and replaced by TinyMCE on the client through the
 * `data-admin-editor` attributes rendered below; initialization lives in
 * `platform/core/base/resources/js/admin/editor.js`, which is lazy-loaded by
 * the `admin.js` Vite entry whenever a `[data-admin-editor]` node exists.
 *
 * Media picker bridge contract (core never references the media package):
 * the editor dispatches a synchronous `admin:editor-file-picker` CustomEvent
 * on `document` when the user opens the image picker, with:
 *
 * - `detail.callback(callable)`: receives `(string $url, array $meta)` and
 *   must be called with the chosen media URL once a file is selected.
 * - `detail.filetype`: `image`, `media`, or `file` (TinyMCE picker type).
 * - `detail.handled`: listeners that open a picker MUST set this to `true`
 *   synchronously while handling the event. If it is still `false` after
 *   dispatch, the editor falls back to a URL prompt so the editor keeps
 *   working on pages without a media picker.
 *
 * The media picker component ships the matching listener; include
 * `<x-media-picker>` on the same page to enable choosing images from the
 * media library inside the editor.
 */
class Editor extends Component
{
    public string $fieldId;

    public function __construct(
        public string $name,
        public mixed $value = null,
        public ?string $label = null,
        public ?string $id = null,
        public ?string $hint = null,
        public ?string $placeholder = null,
        public int $height = 360,
        public bool $disabled = false,
    ) {
        $this->fieldId = $id ?: Str::of($name)->replace(['[', ']'], ['-', ''])->trim('-')->toString();
    }

    public function render(): View
    {
        return view('core/base::components.admin.editor');
    }
}
