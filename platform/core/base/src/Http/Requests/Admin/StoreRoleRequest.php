<?php

namespace Sitewyn\Core\Base\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Sitewyn\Core\Base\Support\PermissionRegistry;

class StoreRoleRequest extends FormRequest
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
            // Match the Botble-style role form counters (0/120 name, 0/250
            // description); seeded role names are well below these limits.
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash:ascii', Rule::unique('roles', 'slug')],
            'description' => ['nullable', 'string', 'max:250'],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in(app(PermissionRegistry::class)->all()->pluck('key')->all())],
        ];
    }
}
