<?php

namespace Sitewyn\Core\Base\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
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

        $tree = $this->permissionTree();

        return view('core/base::admin.roles.create', [
            'role' => new Role,
            'flags' => $tree['flags'],
            'children' => $tree['children'],
            'active' => [],
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

        $tree = $this->permissionTree();

        return view('core/base::admin.roles.edit', [
            'role' => $role,
            'flags' => $tree['flags'],
            'children' => $tree['children'],
            'active' => $role->permissions()->pluck('key')->all(),
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
     * Permission flags data for the role form, built the same way Botble's
     * ACL RoleForm builds its tree: every permission key is split on dots
     * into path segments ("system.users.create" → system → users → create),
     * each segment becomes a flag with a parent flag, and children are
     * grouped by that parent with the algorithm Botble uses. Grouping flags
     * have no permission behind them (their checkboxes submit nothing);
     * real flags carry the original registry key.
     *
     * @return array{flags: array<string, array{flag: string, name: string, parent_flag: string, permission: string|null}>, children: array<string, list<string>>}
     */
    private function permissionTree(): array
    {
        $flags = [];

        foreach (Permission::query()->orderBy('key')->get() as $permission) {
            $segments = explode('.', $permission->key);
            $path = '';
            $parent = 'root';

            foreach (array_slice($segments, 0, -1) as $segment) {
                $path = $path === '' ? $segment : $path.'.'.$segment;
                $flags[$path] = [
                    'flag' => $path,
                    'name' => Str::headline(str_replace('.', ' ', $path)),
                    'parent_flag' => $parent,
                    'permission' => null,
                ];
                $parent = $path;
            }

            $flags[$permission->key] = [
                'flag' => $permission->key,
                'name' => $permission->name ?: Str::headline(str_replace('.', ' ', end($segments))),
                'parent_flag' => $parent,
                'permission' => $permission->key,
            ];
        }

        return [
            'flags' => $flags,
            'children' => $this->getPermissionTree($flags),
        ];
    }

    /**
     * Cloned from Botble's ACL RoleForm::getPermissionTree().
     *
     * @param  array<string, array{flag: string, name: string, parent_flag: string, permission: string|null}>  $permissions
     * @return array<string, list<string>>
     */
    private function getPermissionTree(array $permissions): array
    {
        $sortedFlag = $permissions;
        sort($sortedFlag);

        $children['root'] = $this->getChildren('root', $sortedFlag);

        foreach (array_keys($permissions) as $key) {
            $childrenReturned = $this->getChildren($key, $permissions);
            if (count($childrenReturned) > 0) {
                $children[$key] = $childrenReturned;
            }
        }

        return $children;
    }

    /**
     * Cloned from Botble's ACL RoleForm::getChildren().
     *
     * @param  array<string, array{flag: string, name: string, parent_flag: string, permission: string|null}>  $flags
     * @return list<string>
     */
    private function getChildren(string $parentFlag, array $flags): array
    {
        $newFlags = [];

        foreach ($flags as $item) {
            if (Arr::get($item, 'parent_flag', 'root') === $parentFlag) {
                $newFlags[] = $item['flag'];
            }
        }

        return $newFlags;
    }
}
