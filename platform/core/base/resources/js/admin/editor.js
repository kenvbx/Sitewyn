/**
 * TinyMCE bridge for the `x-admin-editor` Blade component.
 *
 * Replaces every `[data-admin-editor]` textarea with TinyMCE. Media and file
 * picking is delegated through the `admin:editor-file-picker` CustomEvent on
 * `document` (contract documented on the Admin\Editor component): listeners
 * that open their own picker must set `detail.handled = true` synchronously
 * and call `detail.callback(url, meta)` with the chosen URL. When no listener
 * claims the event the editor falls back to a URL prompt.
 */

const DEFAULT_HEIGHT = 360;
const SKIN_URL = '/vendor/tinymce/skins/ui/oxide';
const CONTENT_CSS = '/vendor/tinymce/skins/content/default';

let tinymcePromise = null;

function loadTinymce() {
    if (! tinymcePromise) {
        tinymcePromise = (async () => {
            // The core build assigns `window.tinymce`; themes, models and
            // plugins register themselves on that global, so the core import
            // must settle before the rest.
            await import('tinymce/tinymce');

            await Promise.all([
                import('tinymce/themes/silver'),
                import('tinymce/icons/default'),
                import('tinymce/models/dom'),
                import('tinymce/plugins/lists'),
                import('tinymce/plugins/link'),
                import('tinymce/plugins/image'),
                import('tinymce/plugins/code'),
                import('tinymce/plugins/table'),
            ]);

            return window.tinymce;
        })();
    }

    return tinymcePromise;
}

function promptForUrl(filetype) {
    const label = filetype === 'image' ? 'image' : filetype === 'media' ? 'media' : 'file';

    return window.prompt(`Enter the ${label} URL:`);
}

function filePickerCallback(callback, value, meta) {
    const detail = {
        callback,
        filetype: meta?.filetype ?? 'file',
        handled: false,
    };

    document.dispatchEvent(new CustomEvent('admin:editor-file-picker', { detail }));

    if (detail.handled === false) {
        const url = promptForUrl(detail.filetype);

        if (url) {
            callback(url, {});
        }
    }
}

export function initEditors() {
    document.querySelectorAll('textarea[data-admin-editor]').forEach((node) => {
        if (node.dataset.adminEditorReady || node.disabled) {
            return;
        }

        node.dataset.adminEditorReady = 'true';

        loadTinymce().then((tinymce) => {
            tinymce.init({
                target: node,
                height: parseInt(node.dataset.adminEditorHeight || '', 10) || DEFAULT_HEIGHT,
                placeholder: node.dataset.adminEditorPlaceholder || '',
                branding: false,
                menubar: 'File Edit View Format',
                plugins: 'lists link image code table',
                toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | link image | code',
                skin_url: SKIN_URL,
                content_css: CONTENT_CSS,
                file_picker_callback: filePickerCallback,
                init_instance_callback: (editor) => {
                    // Keep the hidden textarea in sync for normal form submits.
                    editor.on('change keyup', () => editor.save());
                },
            });
        });
    });
}
