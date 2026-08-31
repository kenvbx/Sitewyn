<?php

namespace Sitewyn\Core\Base\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeamUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Team user updates on /admin/system/users: the only surface that
     * accepts roles and the super admin flag.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var User|null $user */
        $user = $this->route('user');

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255', 'alpha_dash:ascii', Rule::unique('users', 'username')->ignore($user?->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'avatar_remove' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'is_super_admin' => ['nullable', 'boolean'],
            'roles' => ['array'],
            'roles.*' => ['integer', Rule::exists('roles', 'id')],
        ];

        // Current password is only ever collected on self-edits: an admin
        // changing somebody else's password must not need to know (or be
        // able to submit) the target's current password.
        if (auth('admin')->id() === $user?->id) {
            $rules['current_password'] = ['nullable', 'string', 'required_with:password'];
        }

        return $rules;
    }
}
