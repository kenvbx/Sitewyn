<?php

namespace Sitewyn\Core\Base\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Http\FormRequest;
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
            'permissionTree' => $this->permissionTree(),
            'selectedPermissions' => [],
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $this->syncRegisteredPermissions();

        $role = Role::query()->create($this->payload($request->validated()));
        $role->permissions()->sync($this->permissionIds($request->validated('permissions', [])));
        admin_flash()->success(__('Role created successfully.'));

        return $this->saveRedirect($request, $role);
    }

    public function edit(Role $role): View
    {
        $this->syncRegisteredPermissions();

        return view('core/base::admin.roles.edit', [
            'role' => $role,
            'permissionTree' => $this->permissionTree(),
            'selectedPermissions' => $role->permissions()->pluck('key')->all(),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $this->syncRegisteredPermissions();

        $role->update($this->payload($request->validated(), $role));
        $role->permissions()->sync($this->permissionIds($request->validated('permissions', [])));
        admin_flash()->success(__('Role updated successfully.'));

        return $this->saveRedirect($request, $role);
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
     * "Save" keeps working on the saved role, "Save and close" (hidden
     * save_and_close input set by the form footer) returns to the index.
     */
    private function saveRedirect(FormRequest $request, Role $role): RedirectResponse
    {
        if ($request->boolean('save_and_close')) {
            return redirect()
                ->route('admin.system.roles.index');
        }

        return redirect()
            ->route('admin.system.roles.edit', $role);
    }

    /**
     * Permission flags tree for the role form: module → feature group →
     * permissions, mirroring how the registry declares them.
     *
     * @return Collection<string, Collection<string, Collection<int, Permission>>>
     */
    private function permissionTree(): Collection
    {
        return Permission::query()
            ->orderBy('module')
            ->orderBy('group')
            ->orderBy('key')
            ->get()
            ->groupBy(fn (Permission $permission): string => $permission->module)
            ->map(fn (Collection $modulePermissions): Collection => $modulePermissions->groupBy(
                fn (Permission $permission): string => $permission->group ?: 'ungrouped',
            ));
    }
}
