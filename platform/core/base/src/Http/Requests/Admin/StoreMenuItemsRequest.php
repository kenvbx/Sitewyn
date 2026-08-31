<?php

namespace Sitewyn\Core\Base\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Sitewyn\Core\Base\Models\MenuItem;

class StoreMenuItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The builder replaces the whole item list on every save. Row ids are
     * request-scoped: existing rows keep their database id, fresh rows send
     * a client-generated placeholder ("n1", ...), and parent_id always
     * references another row's id from this same payload — the server
     * re-creates every row with new ids, so nothing outside the request can
     * be pointed at.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'items' => ['nullable', 'array'],
            'items.*.id' => ['nullable', 'string', 'max:50'],
            'items.*.label' => ['required', 'string', 'max:191'],
            'items.*.type' => ['required', 'string', Rule::in(MenuItem::TYPES)],
            'items.*.target_id' => ['nullable', 'integer', 'required_if:items.*.type,page,post'],
            // Custom links accept site-relative (/path) or http(s) URLs —
            // the scheme allow-list keeps javascript:/data: payloads out of
            // the rendered nav.
            'items.*.url' => [
                'nullable',
                'string',
                'max:500',
                'required_if:items.*.type,custom',
                'regex:#^(/.*|https?://\S+)$#i',
            ],
            'items.*.parent_id' => ['nullable', 'string', 'max:50'],
            'items.*.order' => ['nullable', 'integer'],
        ];
    }

    /**
     * Cross-row checks that the per-item rules cannot express: target rows
     * must really exist (no FK across packages), ids must be unique, and
     * parents must be another row of this request at most one level deep.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $items = $this->input('items', []);
            $items = is_array($items) ? $items : [];

            $ids = [];
            $rowsById = [];

            foreach (array_values($items) as $index => $item) {
                $id = (string) ($item['id'] ?? '');

                if ($id === '') {
                    $validator->errors()->add("items.$index.id", 'Each item needs an id.');

                    continue;
                }

                if (isset($rowsById[$id])) {
                    $validator->errors()->add("items.$index.id", 'Duplicate item id '.$id.'.');

                    continue;
                }

                $rowsById[$id] = $item;
                $ids[] = $id;

                $type = (string) ($item['type'] ?? '');
                $targetId = $item['target_id'] ?? null;

                if (in_array($type, [MenuItem::TYPE_PAGE, MenuItem::TYPE_POST], true) && $targetId !== null) {
                    $table = $type === MenuItem::TYPE_PAGE ? 'pages' : 'posts';

                    // Plain where('id', ...) — Query\Builder's dynamic
                    // whereKey would compare a literal "key" string on
                    // SQLite instead of the primary key.
                    if (! DB::table($table)->where('id', (int) $targetId)->exists()) {
                        $validator->errors()->add(
                            "items.$index.target_id",
                            'The selected '.($type === MenuItem::TYPE_PAGE ? 'page' : 'post').' no longer exists.',
                        );
                    }
                }
            }

            foreach (array_values($items) as $index => $item) {
                $parentId = (string) ($item['parent_id'] ?? '');

                if ($parentId === '') {
                    continue;
                }

                $id = (string) ($item['id'] ?? '');

                if ($parentId === $id) {
                    $validator->errors()->add("items.$index.parent_id", 'An item cannot be its own parent.');

                    continue;
                }

                if (! in_array($parentId, $ids, true)) {
                    $validator->errors()->add(
                        "items.$index.parent_id",
                        'The parent item must be another item of the same menu.',
                    );

                    continue;
                }

                if ((string) ($rowsById[$parentId]['parent_id'] ?? '') !== '') {
                    $validator->errors()->add(
                        "items.$index.parent_id",
                        'Menu items can only be nested one level deep.',
                    );
                }
            }
        });
    }

    /**
     * Items in payload order with normalized scalar fields — the single
     * shape the controller's replace-all transaction consumes.
     *
     * @return array<int, array<string, mixed>>
     */
    public function items(): array
    {
        $items = $this->input('items', []);
        $items = is_array($items) ? array_values($items) : [];

        return collect($items)
            ->map(fn (mixed $item): array => [
                'id' => (string) ($item['id'] ?? ''),
                'label' => (string) ($item['label'] ?? ''),
                'type' => (string) ($item['type'] ?? ''),
                'target_id' => isset($item['target_id']) && $item['target_id'] !== '' ? (int) $item['target_id'] : null,
                'url' => $item['url'] ?? null,
                'parent_id' => (string) ($item['parent_id'] ?? ''),
            ])
            ->all();
    }
}
