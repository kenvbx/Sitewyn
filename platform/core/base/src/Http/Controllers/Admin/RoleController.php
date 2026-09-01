<?php

namespace Sitewyn\Core\Base\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Sitewyn\Core\Base\Http\Requests\Admin\StoreRoleRequest;
use Sitewyn\Core\Base\Http\Requests\Admin\UpdateRoleRequest;
use Sitewyn\Core\Base\Models\Permission;
use Sitewyn\Core\Base\Models\Role;
use Sitewyn\Core\Base\Support\PermissionRegistry;

class RoleController extends Controller
{
    public function __construct(private readonly PermissionRegistry $permissions) {}

    public function index(): View
    {
        $this->syncRegisteredPermissions();

        return view('core/base::admin.roles.index', [
            'roles' => Role::query()
                ->withCount(['permissions', 'users'])
                ->orderByDesc('is_system')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        $this->syncRegisteredPermissions();

        return view('core/base::admin.roles.create', [
            'role' => new Role,
            'permissionGroups' => $this->permissionGroups(),
            'selectedPermissions' => [],
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $this->syncRegisteredPermissions();

        $role = Role::query()->create($this->payload($request->validated()));
        $role->permissions()->sync($this->permissionIds($request->validated('permissions', [])));
        admin_flash()->success(__('Role created successfully.'));

        return redirect()
            ->route('admin.system.roles.index');
    }

    public function edit(Role $role): View
    {
        $this->syncRegisteredPermissions();

        return view('core/base::admin.roles.edit', [
            'role' => $role,
            'permissionGroups' => $this->permissionGroups(),
            'selectedPermissions' => $role->permissions()->pluck('key')->all(),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $this->syncRegisteredPermissions();

        $role->update($this->payload($request->validated(), $role));
        $role->permissions()->sync($this->permissionIds($request->validated('permissions', [])));
        admin_flash()->success(__('Role updated successfully.'));

        return redirect()
            ->route('admin.system.roles.edit', $role);
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->users()->exists()) {
            admin_flash()->error(__('Cannot delete a role that has users.'));

            return redirect()
                ->route('admin.system.roles.index');
        }

        if ($role->is_system) {
            admin_flash()->error(__('System roles cannot be deleted.'));

            return redirect()
                ->route('admin.system.roles.index');
        }

        $role->permissions()->detach();
        $role->delete();
        admin_flash()->success(__('Role deleted successfully.'));

        return redirect()
            ->route('admin.system.roles.index');
    }

    private function syncRegisteredPermissions(): void
    {
        $this->permissions->all()->each(fn (array $permission) => Permission::query()->updateOrCreate(
            ['key' => $permission['key']],
            $permission,
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $validated, ?Role $role = null): array
    {
        return [
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'is_system' => $role?->is_system ?? false,
        ];
    }

    /**
     * @return Collection<int, int>
     */
    private function permissionIds(array $permissionKeys)
    {
        return Permission::query()
            ->whereIn('key', $permissionKeys)
            ->pluck('id');
    }

    /**
     * @return Collection<string, Collection<int, Permission>>
     */
    private function permissionGroups()
    {
        return Permission::query()
            ->orderBy('group')
            ->orderBy('key')
            ->get()
            ->groupBy(fn (Permission $permission): string => $permission->group ?: 'ungrouped');
    }
}
