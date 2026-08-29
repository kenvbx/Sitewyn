<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Sitewyn\Core\Base\Models\Permission;
use Sitewyn\Core\Base\Models\Role;
use Sitewyn\Core\Base\Support\AdminMenuRegistry;
use Sitewyn\Core\Base\Support\PermissionRegistry;
use Sitewyn\Packages\Media\Models\MediaFile;
use Sitewyn\Packages\Media\Models\MediaFolder;
use Tests\TestCase;

class AdminMediaPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_permissions_are_registered(): void
    {
        $registry = $this->app->make(PermissionRegistry::class);

        $this->assertTrue($registry->has('media.index'));
        $this->assertTrue($registry->has('media.upload'));
        $this->assertTrue($registry->has('media.edit'));
        $this->assertTrue($registry->has('media.delete'));
        $this->assertSame('media', $registry->all()->firstWhere('key', 'media.index')['group']);
    }

    public function test_permission_sync_persists_media_permissions(): void
    {
        $this->artisan('permission:sync')
            ->expectsOutputToContain('Synced 14 permissions.')
            ->assertSuccessful();

        foreach (['media.index', 'media.upload', 'media.edit', 'media.delete'] as $key) {
            $this->assertDatabaseHas('permissions', [
                'key' => $key,
                'module' => 'package/media',
                'group' => 'media',
            ]);
        }
    }

    public function test_media_sidebar_requires_media_index_permission(): void
    {
        $registry = $this->app->make(AdminMenuRegistry::class);

        $viewer = $this->userWithPermissions(['media.index']);
        $plainUser = User::factory()->create([
            'is_super_admin' => false,
            'is_active' => true,
        ]);

        $this->assertTrue($registry->visibleFor($viewer)->pluck('id')->contains('media'));
        $this->assertFalse($registry->visibleFor($plainUser)->pluck('id')->contains('media'));
    }

    public function test_media_routes_require_expected_permissions(): void
    {
        $folder = MediaFolder::query()->create([
            'name' => 'Products',
            'slug' => 'products',
            'path' => 'products',
        ]);
        $file = MediaFile::query()->create([
            'name' => 'Hero',
            'file_name' => 'hero.jpg',
            'path' => '2026/08/hero.jpg',
            'disk' => 'public',
            'mime_type' => 'image/jpeg',
            'size' => 100,
        ]);

        $this->actingAs($this->plainAdmin(), 'admin')
            ->get('/admin/media')
            ->assertForbidden();

        $this->actingAs($this->userWithPermissions(['media.index']), 'admin')
            ->get('/admin/media')
            ->assertOk();

        $this->actingAs($this->plainAdmin(), 'admin')
            ->post('/admin/media/upload', [
                'file' => UploadedFile::fake()->create('report.txt', 1, 'text/plain'),
            ])
            ->assertForbidden();

        $this->actingAs($this->plainAdmin(), 'admin')
            ->patch('/admin/media/files/'.$file->id, [
                'name' => 'Updated',
            ])
            ->assertForbidden();

        $this->actingAs($this->plainAdmin(), 'admin')
            ->delete('/admin/media/folders/'.$folder->id)
            ->assertForbidden();
    }

    public function test_media_manager_hides_actions_without_specific_permissions(): void
    {
        $folder = MediaFolder::query()->create([
            'name' => 'Products',
            'slug' => 'products',
            'path' => 'products',
        ]);
        $file = MediaFile::query()->create([
            'name' => 'Hero',
            'file_name' => 'hero.jpg',
            'path' => '2026/08/hero.jpg',
            'disk' => 'public',
            'mime_type' => 'image/jpeg',
            'size' => 100,
        ]);

        $this->actingAs($this->userWithPermissions(['media.index']), 'admin')
            ->get('/admin/media')
            ->assertOk()
            ->assertDontSee('data-bs-target="#media-upload-modal"', false)
            ->assertDontSee('data-bs-target="#media-folder-rename-'.$folder->id.'"', false)
            ->assertDontSee('data-bs-target="#media-file-delete-'.$file->id.'"', false);

        $this->actingAs($this->userWithPermissions(['media.index', 'media.upload', 'media.edit', 'media.delete']), 'admin')
            ->get('/admin/media')
            ->assertOk()
            ->assertSee('data-bs-target="#media-upload-modal"', false)
            ->assertSee('data-bs-target="#media-folder-rename-'.$folder->id.'"', false)
            ->assertSee('data-bs-target="#media-file-delete-'.$file->id.'"', false);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function userWithPermissions(array $permissions): User
    {
        $user = $this->plainAdmin();
        $role = Role::factory()->create();

        foreach ($permissions as $key) {
            $permission = Permission::query()->firstOrCreate(
                ['key' => $key],
                [
                    'name' => $key,
                    'module' => 'package/media',
                    'group' => 'media',
                    'description' => null,
                ],
            );

            $role->permissions()->attach($permission);
        }

        $user->roles()->attach($role);

        return $user;
    }

    private function plainAdmin(): User
    {
        return User::factory()->create([
            'is_super_admin' => false,
            'is_active' => true,
        ]);
    }
}
