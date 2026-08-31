<?php

namespace Sitewyn\Core\Base\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Sitewyn\Core\Base\Support\ThemeManager;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'site_name' => ['required', 'string', 'max:255'],
            'site_logo' => ['nullable', 'string', 'max:2048'],
            'robots_txt' => ['nullable', 'string', 'max:2000'],
            // Nullable so payloads without the field (older clients) keep the
            // current theme; a present value must be a discoverable theme.
            'active_theme' => ['nullable', 'string', Rule::in(app(ThemeManager::class)->availableSlugs())],
        ];
    }
}
