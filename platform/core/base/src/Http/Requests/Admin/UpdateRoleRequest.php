<?php

namespace Sitewyn\Core\Base\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Sitewyn\Core\Base\Models\Role;
use Sitewyn\Core\Base\Support\PermissionRegistry;

class UpdateRoleRequest extends FormRequest
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
        /** @var Role|null $role */
        $role = $this->route('role');

        return [
            // Match the Botble-style role form counters (0/120 name, 0/250
            // description); seeded role names are well below these limits.
            'name' => ['required', 'string', 'max:120'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash:ascii',
                Rule::unique('roles', 'slug')->ignore($role?->id),
            ],
            'description' => ['nullable', 'string', 'max:250'],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in(app(PermissionRegistry::class)->all()->pluck('key')->all())],
        ];
    }
}
