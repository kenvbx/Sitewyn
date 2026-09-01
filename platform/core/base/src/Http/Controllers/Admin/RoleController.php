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

        $tree = $this->permissionTree();

        return view('core/base::admin.roles.create', [
            'role' => new Role,
            'modules' => $tree,
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
            'modules' => $tree,
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
     * Permission flags tree for the role form, matching the rendered Botble
     * ACL permissions screen: one card per registry module (Core / Pages /
     * Blog / Media), one feature item per registry group inside it, the
     * group's *.index permission riding on the feature checkbox and the
     * remaining actions as plain leaves. The registry stays the single
     * source of permission data — the maps below only carry presentation
     * details (display names, card/group order, leaf verbs) Botble encodes
     * in its permission translations.
     *
     * Shape (what the role form's permissions tree renders):
     *   [
     *     'name' => 'Core',                       // module card (bg-success-lt)
     *     'features' => [
     *       // feature with real .index permission + action leaves:
     *       ['name' => 'Users', 'permission' => 'users.index', 'children' => [
     *         ['key' => 'users.create', 'text' => 'Create'], ...
     *       ]],
     *       // grouping feature without permission behind it, holding the
     *       // only sub level in the tree (yellow badge):
     *       ['name' => 'System', 'permission' => null, 'children' => [
     *         ['name' => 'System Users', 'permission' => null, 'children' => [...]],
     *       ]],
     *       // single-permission feature rendered flat with its badge:
     *       ['name' => 'Permissions', 'permission' => 'permissions.index', 'children' => []],
     *       // single-action feature rendered as a bare leaf:
     *       ['leaf' => ['key' => 'menus.manage', 'text' => 'Manage']],
     *     ],
     *   ], ...
     *
     * @return list<array{name: string, features: list<array{name?: string, permission?: string|null, leaf?: array{key: string, text: string}, children?: list<array<string, mixed>}>}>
     */
    private function permissionTree(): array
    {
        $moduleNames = [
            'core/base' => 'Core',
            'package/page' => 'Pages',
            'package/blog' => 'Blog',
            'package/media' => 'Media',
        ];

        $moduleOrder = ['core/base' => 0, 'package/page' => 1, 'package/blog' => 2, 'package/media' => 3];

        $groupOrder = [
            'core/base' => ['users', 'system users', 'roles', 'permissions', 'audit', 'settings', 'plugins', 'backups', 'menus', 'widgets'],
            'package/page' => ['page'],
            'package/blog' => ['post', 'category', 'tag'],
            'package/media' => ['media'],
        ];

        // Feature badge text per registry group (Str::headline fallback).
        $featureNames = [
            'users' => 'Users',
            'roles' => 'Roles',
            'permissions' => 'Permissions',
            'audit' => 'Audit',
            'page' => 'Pages',
            'post' => 'Posts',
            'category' => 'Categories',
            'tag' => 'Tags',
            'media' => 'Media',
        ];

        // Leaf verbs per action segment, mirroring the short verbs Botble's
        // tree leaves use (Create/Edit/Delete/...).
        $leafTexts = [
            'index' => 'View list',
            'create' => 'Create',
            'edit' => 'Edit',
            'delete' => 'Delete',
            'upload' => 'Upload',
            'manage' => 'Manage',
        ];

        // Registry permissions bucketed module → group → key (keys arrive
        // sorted from the query, and groups inherit that order).
        $grouped = [];

        foreach (Permission::query()->orderBy('key')->get() as $permission) {
            $grouped[$permission->module][$permission->group ?? ''][$permission->key] = $permission;
        }

        $tree = [];

        foreach (collect($grouped)
            ->sortBy(fn (array $groups, string $module): int => $moduleOrder[$module] ?? count($moduleOrder), SORT_NUMERIC)
            ->all() as $module => $groups) {
            // Declared groups first in their display order, then any group
            // registered later alphabetically.
            $order = $groupOrder[$module] ?? [];

            $orderedGroups = collect($order)
                ->filter(fn (string $group): bool => isset($groups[$group]))
                ->merge(
                    collect($groups)
                        ->keys()
                        ->reject(fn (string $group): bool => in_array($group, $order, true))
                        ->sort()
                        ->values()
                );

            $features = [];

            foreach ($orderedGroups as $group) {
                $groupPermissions = collect($groups[$group])->values();
                $leafOf = fn ($permission): array => [
                    'key' => $permission->key,
                    'text' => $leafTexts[Str::afterLast($permission->key, '.')] ?? $permission->name,
                ];

                if ($group === 'system users') {
                    // The only sub level: a "System" feature whose single
                    // yellow "System Users" node groups the system.users.*
                    // leaves. Both checkboxes are grouping nodes (no name —
                    // system.users is not a submittable permission).
                    $features[] = [
                        'name' => 'System',
                        'permission' => null,
                        'children' => [
                            [
                                'name' => 'System Users',
                                'permission' => null,
                                'children' => $groupPermissions->map($leafOf)->all(),
                            ],
                        ],
                    ];

                    continue;
                }

                if ($groupPermissions->count() === 1) {
                    // Single-action features (Settings/Plugins/Backups/
                    // Menus/Widgets) render flat: the feature li holds the
                    // leaf directly, without hitarea or badge.
                    $features[] = ['leaf' => $leafOf($groupPermissions->first())];

                    continue;
                }

                $indexPermission = $groupPermissions->first(
                    fn ($permission): bool => Str::afterLast($permission->key, '.') === 'index'
                );

                $features[] = [
                    'name' => $featureNames[$group] ?? Str::headline($group),
                    'permission' => $indexPermission?->key,
                    'children' => $groupPermissions
                        ->reject(fn ($permission): bool => $indexPermission !== null && $permission->key === $indexPermission->key)
                        ->map($leafOf)
                        ->all(),
                ];
            }

            $tree[] = ['name' => $moduleNames[$module] ?? Str::headline($module), 'features' => $features];
        }

        return $tree;
    }
}
