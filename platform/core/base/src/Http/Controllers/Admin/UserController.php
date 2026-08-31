<?php

namespace Sitewyn\Core\Base\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Sitewyn\Core\Base\Http\Requests\Admin\StoreUserRequest;
use Sitewyn\Core\Base\Http\Requests\Admin\UpdateUserRequest;
use Sitewyn\Core\Base\Models\Role;

class UserController extends Controller
{
    public function index(): View
    {
        // User management manages the platform team only: super admins and
        // holders of the built-in Admin role (see
        // PlatformAdminController::isTeamMember()). Everyone else is not part
        // of the list — they cannot be found, edited or deleted from this UI.
        return view('core/base::admin.users.index', [
            'users' => User::query()
                ->where(function (Builder $query): void {
                    $query->where('is_super_admin', true)
                        ->orWhereHas('roles', fn (Builder $roles) => $roles->where('slug', 'admin'));
                })
                ->with('roles')
                ->withCount('roles')
                ->orderByDesc('is_super_admin')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('core/base::admin.users.create', [
            'user' => new User,
            'roles' => $this->assignableRoles(),
            'selectedRoles' => [],
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->assertPrivilegesAssignable($validated);

        $user = User::query()->create($this->payload($validated));
        $user->roles()->sync($validated['roles'] ?? []);
        admin_flash()->success(__('User created successfully.'));

        return redirect()
            ->route('admin.users.index');
    }

    public function edit(User $user): View
    {
        return view('core/base::admin.users.edit', [
            'user' => $user,
            'roles' => $this->assignableRoles(),
            'selectedRoles' => $user->roles()->pluck('roles.id')->all(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();
        $editingSelf = $this->isEditingSelf($user);

        if ($editingSelf) {
            // Nobody may change their own privileges, not even super admins.
            unset($validated['roles'], $validated['is_super_admin']);
        } else {
            $this->assertPrivilegesAssignable($validated);
        }

        $user->update($this->payload($validated, $user));

        if (! $editingSelf) {
            $user->roles()->sync($validated['roles'] ?? []);
        }

        admin_flash()->success(__('User updated successfully.'));

        return redirect()
            ->route('admin.users.edit', $user);
    }

    public function destroy(User $user): RedirectResponse
    {
        if (Auth::guard('admin')->id() === $user->id) {
            admin_flash()->error(__('You cannot delete your own account.'));

            return redirect()
                ->route('admin.users.index');
        }

        $user->roles()->detach();
        $user->delete();
        admin_flash()->success(__('User deleted successfully.'));

        return redirect()
            ->route('admin.users.index');
    }

    /**
     * Privilege escalation guards: only super admins may grant the super admin
     * flag, and non-super admins may only assign roles whose permissions are a
     * subset of their own. Self-edits are stripped earlier by the caller.
     *
     * @param  array<string, mixed>  $validated
     *
     * @throws ValidationException
     */
    private function assertPrivilegesAssignable(array $validated): void
    {
        $this->assertSuperAdminFlagAllowed($validated);
        $this->assertRolesAssignable($validated);
    }

    /**
     * @param  array<string, mixed>  $validated
     *
     * @throws ValidationException
     */
    private function assertSuperAdminFlagAllowed(array $validated): void
    {
        $requester = Auth::guard('admin')->user();

        if (! empty($validated['is_super_admin']) && ! $requester->is_super_admin) {
            throw ValidationException::withMessages([
                'is_super_admin' => __('Only super admins can grant the super admin flag.'),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     *
     * @throws ValidationException
     */
    private function assertRolesAssignable(array $validated): void
    {
        if (empty($validated['roles'])) {
            return;
        }

        $requester = Auth::guard('admin')->user();

        if ($requester->is_super_admin) {
            return;
        }

        $allowedKeys = $requester->permissionKeys();

        $roles = Role::query()
            ->whereIn('id', $validated['roles'])
            ->with('permissions')
            ->get();

        foreach ($roles as $role) {
            if ($role->permissions->pluck('key')->diff($allowedKeys)->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'roles' => __('You cannot assign a role with permissions you do not have.'),
                ]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $validated, ?User $user = null): array
    {
        $editingSelf = $user !== null && $this->isEditingSelf($user);

        $payload = [
            'name' => $validated['name'],
            'username' => $validated['username'] ?? null,
            'email' => $validated['email'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'is_super_admin' => $editingSelf
                ? (bool) $user->is_super_admin
                : (bool) ($validated['is_super_admin'] ?? false),
        ];

        if (($validated['password'] ?? '') !== '') {
            $payload['password'] = $validated['password'];
        }

        if ($editingSelf) {
            $payload['is_active'] = true;
        }

        return $payload;
    }

    private function isEditingSelf(User $user): bool
    {
        return Auth::guard('admin')->id() === $user->id;
    }

    /**
     * Roles the current admin may render on user forms: every role for super
     * admins, otherwise only roles whose permissions are a subset of the
     * requester's own permissions.
     *
     * @return Collection<int, Role>
     */
    private function assignableRoles()
    {
        $roles = Role::query()
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->with('permissions')
            ->get();

        $requester = Auth::guard('admin')->user();

        if ($requester->is_super_admin) {
            return $roles;
        }

        $allowedKeys = $requester->permissionKeys();

        return $roles
            ->reject(fn (Role $role): bool => $role->permissions->pluck('key')->diff($allowedKeys)->isNotEmpty())
            ->values();
    }
}
