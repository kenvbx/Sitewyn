<?php

namespace Sitewyn\Core\Base\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Sitewyn\Core\Base\Models\Menu;

class StoreMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Slug shape only — uniqueness is handled with the SlugService suffix
     * pattern (like pages/posts/categories), not a hard 422: a duplicated
     * slug gets -2, -3, ... appended on save.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'slug' => ['nullable', 'string', 'max:191'],
            'location' => ['nullable', 'string', Rule::in([Menu::LOCATION_PRIMARY])],
        ];
    }
}
