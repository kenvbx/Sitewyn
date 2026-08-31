<?php

namespace Sitewyn\Packages\Blog\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Sitewyn\Core\Base\Support\Translations;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'parent_id' => [
                'nullable',
                Rule::exists('categories', 'id'),
            ],
            'translations' => ['nullable', 'array', Translations::localeKeyRule()],
            'translations.*.name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
