<?php

namespace Sitewyn\Packages\Blog\Http\Requests\Admin;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Sitewyn\Packages\Blog\Models\Category;

class UpdateCategoryRequest extends FormRequest
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
        /** @var Category|null $category */
        $category = $this->route('category');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'parent_id' => [
                'nullable',
                Rule::exists('categories', 'id'),
                // The parent select already hides the category itself and its
                // subtree; this closure keeps crafted requests from forming a
                // cycle the UI cannot express.
                function (string $attribute, mixed $value, Closure $fail) use ($category): void {
                    if ($category === null || ! is_numeric($value)) {
                        return;
                    }

                    $parentId = (int) $value;

                    if ($parentId === (int) $category->id) {
                        $fail('A category cannot be its own parent.');

                        return;
                    }

                    if ($category->descendants()->contains('id', $parentId)) {
                        $fail('The selected parent is a descendant of this category.');
                    }
                },
            ],
        ];
    }
}
