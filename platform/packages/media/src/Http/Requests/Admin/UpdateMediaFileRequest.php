<?php

namespace Sitewyn\Packages\Media\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMediaFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:190'],
            'folder_id' => ['sometimes', 'nullable', 'integer', Rule::exists('media_folders', 'id')],
        ];
    }
}
