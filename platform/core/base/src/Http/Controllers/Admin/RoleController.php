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
     * @return list<array{name: string, features: list<array{name?: string, permission?: string|null, leaf?: array{key: string, text: string}, children?: list<array<string, mixed>}>}>
     */
    private function permissionTree(): array
    {
        $moduleNames = [
            'cms' => 'CMS',
            'settings' => 'Settings',
            'system' => 'System',
            'tools' => 'Tools',
        ];

        $moduleOrder = [
            'cms' => 0,
            'settings' => 1,
            'system' => 2,
            'tools' => 3,
        ];

        $featureOrder = [
            'cms' => ['media', 'pages', 'static blocks', 'blog', 'contact', 'custom fields', 'galleries', 'members'],
            'settings' => ['common', 'others', 'sitemap', 'languages', 'localization', 'api'],
            'system' => ['users', 'roles', 'license', 'cronjobs', 'security', 'cleanup', 'system information', 'system updater', 'plugins', 'appearance', 'analytics', 'activity logs', 'backup', 'request logs'],
            'tools' => ['import/export data'],
        ];

        $grouped = [];

        foreach ($this->permissions->all()->sortBy('key') as $permission) {
            $presentation = $this->permissionPresentation($permission);
            $module = $presentation['module'];
            $feature = $presentation['feature'];
            $subgroup = $presentation['subgroup'];
            $permission['presentation_name'] = $presentation['name'];

            if ($subgroup !== null) {
                $grouped[$module][$feature]['children'][$subgroup]['permissions'][$permission['key']] = $permission;
                $grouped[$module][$feature]['children'][$subgroup]['name'] = $presentation['subgroupName'];
                $grouped[$module][$feature]['name'] = $presentation['featureName'];
                $grouped[$module][$feature]['permission'] ??= null;

                continue;
            }

            $grouped[$module][$feature]['permissions'][$permission['key']] = $permission;
            $grouped[$module][$feature]['name'] = $presentation['featureName'];
        }

        $tree = [];

        foreach (collect($grouped)
            ->sortBy(fn (array $features, string $module): int => $moduleOrder[$module] ?? count($moduleOrder), SORT_NUMERIC)
            ->all() as $module => $featuresByName) {
            $order = $featureOrder[$module] ?? [];

            $orderedFeatures = collect($order)
                ->filter(fn (string $feature): bool => isset($featuresByName[$feature]))
                ->merge(
                    collect($featuresByName)
                        ->keys()
                        ->reject(fn (string $feature): bool => in_array($feature, $order, true))
                        ->sort()
                        ->values()
                );

            $features = [];

            foreach ($orderedFeatures as $featureKey) {
                $feature = $featuresByName[$featureKey];
                $groupPermissions = collect($feature['permissions'] ?? [])->values();

                if (! empty($feature['children'])) {
                    $featurePermission = $groupPermissions->first(
                        fn (array $permission): bool => in_array($permission['key'], ['media.index'], true)
                    );
                    $children = $groupPermissions
                        ->reject(fn (array $permission): bool => $featurePermission !== null && $permission['key'] === $featurePermission['key'])
                        ->sortBy(fn (array $permission): array => [
                            $this->permissionSortOrder($permission),
                            $permission['key'],
                        ])
                        ->map(fn (array $permission): array => [
                            'key' => $permission['key'],
                            'text' => $permission['presentation_name'] ?? $this->permissionLeafText($permission),
                        ])
                        ->values()
                        ->all();

                    foreach ($feature['children'] as $child) {
                        $children[] = $this->permissionFeatureNode(
                            $child['name'],
                            collect($child['permissions'] ?? [])->values(),
                        );
                    }

                    $features[] = [
                        'name' => $feature['name'],
                        'permission' => $featurePermission['key'] ?? null,
                        'children' => $children,
                    ];

                    continue;
                }

                $features[] = $this->permissionFeatureNode($feature['name'], $groupPermissions);
            }

            $tree[] = ['name' => $moduleNames[$module] ?? Str::headline($module), 'features' => $features];
        }

        return $tree;
    }

    /**
     * @param  array{name: string, key: string, module: string, group: string|null, description: string|null}  $permission
     * @return array{module: string, feature: string, featureName: string, subgroup: string|null, subgroupName: string|null, name: string}
     */
    private function permissionPresentation(array $permission): array
    {
        $map = [
            'media.index' => ['cms', 'media', 'Media', null, null, 'Media'],
            'media.upload' => ['cms', 'media', 'Media', 'file', 'File', 'Create'],
            'media.edit' => ['cms', 'media', 'Media', 'file', 'File', 'Edit'],
            'media.file.trash' => ['cms', 'media', 'Media', 'file', 'File', 'Trash'],
            'media.delete' => ['cms', 'media', 'Media', 'file', 'File', 'Delete'],
            'media.folder.create' => ['cms', 'media', 'Media', 'folder', 'Folder', 'Create'],
            'media.folder.edit' => ['cms', 'media', 'Media', 'folder', 'Folder', 'Edit'],
            'media.folder.trash' => ['cms', 'media', 'Media', 'folder', 'Folder', 'Trash'],
            'media.folder.delete' => ['cms', 'media', 'Media', 'folder', 'Folder', 'Delete'],
            'page.index' => ['cms', 'pages', 'Pages', null, null, 'Pages'],
            'static_blocks.create' => ['cms', 'static blocks', 'Static Blocks', null, null, 'Create'],
            'static_blocks.edit' => ['cms', 'static blocks', 'Static Blocks', null, null, 'Edit'],
            'static_blocks.delete' => ['cms', 'static blocks', 'Static Blocks', null, null, 'Delete'],
            'post.index' => ['cms', 'blog', 'Blog', 'posts', 'Posts', 'Posts'],
            'category.index' => ['cms', 'blog', 'Blog', 'categories', 'Categories', 'Categories'],
            'tag.index' => ['cms', 'blog', 'Blog', 'tags', 'Tags', 'Tags'],
            'reports.index' => ['cms', 'blog', 'Blog', null, null, 'Reports'],
            'contact.edit' => ['cms', 'contact', 'Contact', null, null, 'Edit'],
            'contact.delete' => ['cms', 'contact', 'Contact', null, null, 'Delete'],
            'contact.custom_fields' => ['cms', 'contact', 'Contact', null, null, 'Custom Fields'],
            'custom_fields.create' => ['cms', 'custom fields', 'Custom Fields', null, null, 'Create'],
            'custom_fields.edit' => ['cms', 'custom fields', 'Custom Fields', null, null, 'Edit'],
            'custom_fields.delete' => ['cms', 'custom fields', 'Custom Fields', null, null, 'Delete'],
            'galleries.create' => ['cms', 'galleries', 'Galleries', null, null, 'Create'],
            'galleries.edit' => ['cms', 'galleries', 'Galleries', null, null, 'Edit'],
            'galleries.delete' => ['cms', 'galleries', 'Galleries', null, null, 'Delete'],
            'members.create' => ['cms', 'members', 'Members', null, null, 'Create'],
            'members.edit' => ['cms', 'members', 'Members', null, null, 'Edit'],
            'members.delete' => ['cms', 'members', 'Members', null, null, 'Delete'],
            'settings.edit' => ['settings', 'common', 'Common', null, null, 'General'],
            'settings.general' => ['settings', 'common', 'Common', null, null, 'General'],
            'settings.email' => ['settings', 'common', 'Common', null, null, 'Email'],
            'settings.media' => ['settings', 'common', 'Common', null, null, 'Media'],
            'settings.admin_appearance' => ['settings', 'common', 'Common', null, null, 'Admin Appearance'],
            'settings.cache' => ['system', 'cache', 'Cache Management', null, null, 'Cache Management'],
            'settings.datatables' => ['settings', 'common', 'Common', null, null, 'Datatables'],
            'settings.email_rules' => ['settings', 'common', 'Common', null, null, 'Email Rules'],
            'settings.phone_number' => ['settings', 'common', 'Common', null, null, 'Phone Number'],
            'settings.permalink' => ['settings', 'common', 'Common', null, null, 'Permalink'],
            'settings.optimize' => ['settings', 'common', 'Common', null, null, 'Optimize'],
            'settings.website_tracking' => ['settings', 'common', 'Common', null, null, 'Website Tracking'],
            'settings.analytics' => ['settings', 'others', 'Others', null, null, 'Analytics'],
            'settings.blog' => ['settings', 'others', 'Others', null, null, 'Blog'],
            'settings.captcha' => ['settings', 'others', 'Others', null, null, 'Captcha'],
            'settings.contact' => ['settings', 'others', 'Others', null, null, 'Contact'],
            'settings.member' => ['settings', 'others', 'Others', null, null, 'Member'],
            'settings.social_login' => ['settings', 'others', 'Others', null, null, 'Social Login'],
            'settings.sitemap' => ['settings', 'sitemap', 'Sitemap', null, null, 'Sitemap'],
            'settings.languages.create' => ['settings', 'languages', 'Languages', null, null, 'Create'],
            'settings.languages.edit' => ['settings', 'languages', 'Languages', null, null, 'Edit'],
            'settings.languages.delete' => ['settings', 'languages', 'Languages', null, null, 'Delete'],
            'settings.localization.locales' => ['settings', 'localization', 'Localization', null, null, 'Locales'],
            'settings.localization.theme_translations' => ['settings', 'localization', 'Localization', null, null, 'Theme translations'],
            'settings.localization.other_translations' => ['settings', 'localization', 'Localization', null, null, 'Other translations'],
            'api.sanctum_tokens.create' => ['settings', 'api', 'API', 'sanctum token', 'Sanctum Token', 'Create'],
            'api.sanctum_tokens.delete' => ['settings', 'api', 'API', 'sanctum token', 'Sanctum Token', 'Delete'],
            'users.index' => ['system', 'users', 'Users', null, null, 'Users'],
            'system.users.index' => ['system', 'users', 'Users', null, null, 'Users'],
            'roles.index' => ['system', 'roles', 'Roles', null, null, 'Roles'],
            'permissions.index' => ['system', 'roles', 'Roles', null, null, 'Permissions'],
            'license.manage' => ['system', 'license', 'Manage license', null, null, 'Manage license'],
            'cronjobs.manage' => ['system', 'cronjobs', 'Cronjob', null, null, 'Cronjob'],
            'security.manage' => ['system', 'security', 'Security Settings', null, null, 'Security Settings'],
            'cleanup.manage' => ['system', 'cleanup', 'Cleanup System', null, null, 'Cleanup System'],
            'system.info' => ['system', 'system information', 'System Information', null, null, 'System Information'],
            'system.updater' => ['system', 'system updater', 'System Updater', null, null, 'System Updater'],
            'plugins.manage' => ['system', 'plugins', 'Plugins', null, null, 'Activate/Deactivate'],
            'plugins.activate' => ['system', 'plugins', 'Plugins', null, null, 'Activate/Deactivate'],
            'plugins.remove' => ['system', 'plugins', 'Plugins', null, null, 'Remove'],
            'plugins.create' => ['system', 'plugins', 'Plugins', null, null, 'Add New Plugins'],
            'menus.manage' => ['system', 'appearance', 'Appearance', 'menu', 'Menu', 'Edit'],
            'appearance.theme.activate' => ['system', 'appearance', 'Appearance', 'theme', 'Theme', 'Activate'],
            'appearance.theme.remove' => ['system', 'appearance', 'Appearance', 'theme', 'Theme', 'Remove'],
            'appearance.theme_options' => ['system', 'appearance', 'Appearance', null, null, 'Theme options'],
            'appearance.custom_css' => ['system', 'appearance', 'Appearance', null, null, 'Custom CSS'],
            'appearance.custom_js' => ['system', 'appearance', 'Appearance', null, null, 'Custom JS'],
            'appearance.custom_html' => ['system', 'appearance', 'Appearance', null, null, 'Custom HTML'],
            'appearance.robots' => ['system', 'appearance', 'Appearance', null, null, 'Robots.txt Editor'],
            'widgets.manage' => ['system', 'appearance', 'Appearance', null, null, 'Widgets'],
            'analytics.top_page' => ['system', 'analytics', 'Analytics', null, null, 'Top Page'],
            'analytics.top_browser' => ['system', 'analytics', 'Analytics', null, null, 'Top Browser'],
            'analytics.top_referrer' => ['system', 'analytics', 'Analytics', null, null, 'Top Referrer'],
            'audit.index' => ['system', 'activity logs', 'Activity Logs', null, null, 'Activity Logs'],
            'activity_logs.delete' => ['system', 'activity logs', 'Activity Logs', null, null, 'Delete'],
            'backups.manage' => ['system', 'backup', 'Backup', null, null, 'Backup'],
            'backups.create' => ['system', 'backup', 'Backup', null, null, 'Create'],
            'backups.restore' => ['system', 'backup', 'Backup', null, null, 'Restore'],
            'backups.delete' => ['system', 'backup', 'Backup', null, null, 'Delete'],
            'request_logs.index' => ['system', 'request logs', 'Request Logs', null, null, 'Request Logs'],
            'request_logs.delete' => ['system', 'request logs', 'Request Logs', null, null, 'Delete'],
        ];

        if (str_starts_with($permission['key'], 'tools.')) {
            $name = Str::headline(Str::after($permission['key'], 'tools.'));

            return [
                'module' => 'tools',
                'feature' => 'import/export data',
                'featureName' => 'Import/Export Data',
                'subgroup' => null,
                'subgroupName' => null,
                'name' => $name,
            ];
        }

        $entry = $map[$permission['key']] ?? null;

        if ($entry === null && str_starts_with($permission['key'], 'page.')) {
            $entry = ['cms', 'pages', 'Pages', null, null, $this->permissionLeafText($permission)];
        }

        if ($entry === null && str_starts_with($permission['key'], 'post.')) {
            $entry = ['cms', 'blog', 'Blog', 'posts', 'Posts', $this->permissionLeafText($permission)];
        }

        if ($entry === null && str_starts_with($permission['key'], 'category.')) {
            $entry = ['cms', 'blog', 'Blog', 'categories', 'Categories', $this->permissionLeafText($permission)];
        }

        if ($entry === null && str_starts_with($permission['key'], 'tag.')) {
            $entry = ['cms', 'blog', 'Blog', 'tags', 'Tags', $this->permissionLeafText($permission)];
        }

        if ($entry === null && str_starts_with($permission['key'], 'roles.')) {
            $entry = ['system', 'roles', 'Roles', null, null, $this->permissionLeafText($permission)];
        }

        if ($entry === null && str_starts_with($permission['key'], 'users.')) {
            $entry = ['system', 'users', 'Users', null, null, $this->permissionLeafText($permission)];
        }

        if ($entry === null && str_starts_with($permission['key'], 'system.users.')) {
            $entry = ['system', 'users', 'Users', null, null, $this->permissionLeafText($permission)];
        }

        if ($entry === null) {
            $group = $permission['group'] ?? 'ungrouped';
            $entry = [$permission['module'], $group, Str::headline($group), null, null, $this->permissionLeafText($permission)];
        }

        return [
            'module' => $entry[0],
            'feature' => $entry[1],
            'featureName' => $entry[2],
            'subgroup' => $entry[3],
            'subgroupName' => $entry[4],
            'name' => $entry[5],
        ];
    }

    /**
     * @param  Collection<int, array{name: string, key: string, module: string, group: string|null, description: string|null, presentation_name?: string}>  $permissions
     * @return array{name: string, permission: string|null, children: list<array{key: string, text: string}>}
     */
    private function permissionFeatureNode(string $name, Collection $permissions): array
    {
        $permissions = $permissions
            ->sortBy(fn (array $permission): array => [
                $this->permissionSortOrder($permission),
                $permission['key'],
            ])
            ->values();

        $indexPermission = $permissions->first(
            fn (array $permission): bool => Str::afterLast($permission['key'], '.') === 'index'
        );

        if ($indexPermission === null && $permissions->count() === 1) {
            $indexPermission = $permissions->first();
        }

        return [
            'name' => $name,
            'permission' => $indexPermission['key'] ?? null,
            'children' => $permissions
                ->reject(fn (array $permission): bool => $indexPermission !== null && $permission['key'] === $indexPermission['key'])
                ->map(fn (array $permission): array => [
                    'key' => $permission['key'],
                    'text' => $permission['presentation_name'] ?? $this->permissionLeafText($permission),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  array{name: string, key: string, module: string, group: string|null, description: string|null, presentation_name?: string}  $permission
     */
    private function permissionSortOrder(array $permission): int
    {
        $label = Str::lower($permission['presentation_name'] ?? $this->permissionLeafText($permission));

        return [
            'view list' => 0,
            'create' => 10,
            'edit' => 20,
            'trash' => 30,
            'delete' => 40,
            'restore' => 50,
            'activate' => 60,
            'remove' => 70,
        ][$label] ?? 100;
    }

    /**
     * @param  array{name: string, key: string, module: string, group: string|null, description: string|null}  $permission
     */
    private function permissionLeafText(array $permission): string
    {
        return [
            'index' => 'View list',
            'create' => 'Create',
            'edit' => 'Edit',
            'delete' => 'Delete',
            'upload' => 'Upload',
            'restore' => 'Restore',
            'trash' => 'Trash',
            'manage' => 'Manage',
        ][Str::afterLast($permission['key'], '.')] ?? $permission['name'];
    }
}
