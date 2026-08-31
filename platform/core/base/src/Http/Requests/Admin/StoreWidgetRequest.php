<?php

namespace Sitewyn\Core\Base\Http\Requests\Admin;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Sitewyn\Core\Base\Models\Widget;
use Sitewyn\Core\Base\Support\ThemeManager;

class StoreWidgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * area_slug is validated against the ACTIVE theme's declarations at
     * runtime — areas live in theme.json, not the DB, so a plain static
     * Rule::in list cannot know them. The per-type payload rules mirror
     * the admin form: title is shared, limit belongs to recent-posts,
     * content to text.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $areaSlugs = collect(app(ThemeManager::class)->widgetAreas())
            ->pluck('slug')
            ->all();

        return [
            'area_slug' => ['required', 'string', Rule::in($areaSlugs)],
            'type' => ['required', 'string', Rule::in(Widget::TYPES)],
            'data' => [
                'present',
                'array',
                $this->validateDataForType(...),
            ],
            'data.title' => ['nullable', 'string', 'max:191'],
            'data.limit' => [
                'nullable',
                'required_if:type,'.Widget::TYPE_RECENT_POSTS,
                'integer',
                'min:1',
                'max:20',
            ],
            'data.content' => [
                'nullable',
                'required_if:type,'.Widget::TYPE_TEXT,
                'string',
            ],
        ];
    }

    /**
     * Type-specific payload guarantee on top of the required_if rules:
     * recent-posts must carry a usable limit, text must carry content.
     *
     * @param  Closure(string): void  $fail
     */
    private function validateDataForType(string $attribute, mixed $value, Closure $fail): void
    {
        $type = (string) $this->input('type');

        if ($type === Widget::TYPE_RECENT_POSTS) {
            $limit = $value['limit'] ?? null;

            if (! is_numeric($limit) || (int) $limit < 1 || (int) $limit > 20) {
                $fail('The widget data must include a limit between 1 and 20 for recent posts widgets.');
            }
        }

        if ($type === Widget::TYPE_TEXT && trim((string) ($value['content'] ?? '')) === '') {
            $fail('The widget data must include content for text widgets.');
        }
    }
}
