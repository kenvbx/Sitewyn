<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Sitewyn\Packages\Media\Models\MediaFile;
use Sitewyn\Packages\Media\Models\MediaFolder;
use Tests\TestCase;

class AdminMediaActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_update_media_file(): void
    {
        $file = $this->mediaFile();

        $this->patch('/admin/media/files/'.$file->id, [
            'name' => 'Updated file',
        ])->assertRedirect('/admin/login');
    }

    public function test_admin_can_rename_and_move_media_file(): void
    {
        $admin = $this->adminUser();
        $target = MediaFolder::query()->create([
            'name' => 'Products',
            'slug' => 'products',
            'path' => 'products',
        ]);
        $file = $this->mediaFile();

        $this->actingAs($admin, 'admin')
            ->patch('/admin/media/files/'.$file->id, [
                'name' => 'Product hero',
                'folder_id' => $target->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('media_files', [
            'id' => $file->id,
            'name' => 'Product hero',
            'folder_id' => $target->id,
        ]);
    }

    public function test_admin_can_delete_media_file_and_conversions_from_storage(): void
    {
        Storage::fake('public');
        $admin = $this->adminUser();
        Storage::disk('public')->put('2026/08/hero.jpg', 'original');
        Storage::disk('public')->put('2026/08/conversions/hero-thumb.jpg', 'thumb');
        $file = $this->mediaFile([
            'path' => '2026/08/hero.jpg',
            'conversions' => [
                'thumb' => [
                    'path' => '2026/08/conversions/hero-thumb.jpg',
                ],
            ],
        ]);

        $this->actingAs($admin, 'admin')
            ->delete('/admin/media/files/'.$file->id)
            ->assertRedirect();

        $this->assertDatabaseMissing('media_files', ['id' => $file->id]);
        Storage::disk('public')->assertMissing('2026/08/hero.jpg');
        Storage::disk('public')->assertMissing('2026/08/conversions/hero-thumb.jpg');
    }

    public function test_admin_can_rename_folder_and_refresh_child_paths(): void
    {
        $admin = $this->adminUser();
        $folder = MediaFolder::query()->create([
            'name' => 'Old uploads',
            'slug' => 'old-uploads',
            'path' => 'old-uploads',
        ]);
        $child = MediaFolder::query()->create([
            'parent_id' => $folder->id,
            'name' => 'Children',
            'slug' => 'children',
            'path' => 'old-uploads/children',
        ]);

        $this->actingAs($admin, 'admin')
            ->patch('/admin/media/folders/'.$folder->id, [
                'name' => 'New uploads',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('media_folders', [
            'id' => $folder->id,
            'name' => 'New uploads',
            'slug' => 'new-uploads',
            'path' => 'new-uploads',
        ]);
        $this->assertDatabaseHas('media_folders', [
            'id' => $child->id,
            'path' => 'new-uploads/children',
        ]);
    }

    public function test_admin_can_move_folder_and_refresh_child_paths(): void
    {
        $admin = $this->adminUser();
        $target = MediaFolder::query()->create([
            'name' => 'Library',
            'slug' => 'library',
            'path' => 'library',
        ]);
        $folder = MediaFolder::query()->create([
            'name' => 'Products',
            'slug' => 'products',
            'path' => 'products',
        ]);
        $child = MediaFolder::query()->create([
            'parent_id' => $folder->id,
            'name' => 'Featured',
            'slug' => 'featured',
            'path' => 'products/featured',
        ]);

        $this->actingAs($admin, 'admin')
            ->patch('/admin/media/folders/'.$folder->id, [
                'parent_id' => $target->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('media_folders', [
            'id' => $folder->id,
            'parent_id' => $target->id,
            'path' => 'library/products',
        ]);
        $this->assertDatabaseHas('media_folders', [
            'id' => $child->id,
            'path' => 'library/products/featured',
        ]);
    }

    public function test_admin_cannot_move_folder_into_itself_or_child_folder(): void
    {
        $admin = $this->adminUser();
        $folder = MediaFolder::query()->create([
            'name' => 'Products',
            'slug' => 'products',
            'path' => 'products',
        ]);
        $child = MediaFolder::query()->create([
            'parent_id' => $folder->id,
            'name' => 'Featured',
            'slug' => 'featured',
            'path' => 'products/featured',
        ]);

        $this->actingAs($admin, 'admin')
            ->from('/admin/media')
            ->patch('/admin/media/folders/'.$folder->id, [
                'parent_id' => $folder->id,
            ])
            ->assertRedirect('/admin/media')
            ->assertSessionHasErrors('parent_id');

        $this->actingAs($admin, 'admin')
            ->from('/admin/media')
            ->patch('/admin/media/folders/'.$folder->id, [
                'parent_id' => $child->id,
            ])
            ->assertRedirect('/admin/media')
            ->assertSessionHasErrors('parent_id');
    }

    public function test_admin_can_delete_folder_tree_with_files(): void
    {
        Storage::fake('public');
        $admin = $this->adminUser();
        $folder = MediaFolder::query()->create([
            'name' => 'Products',
            'slug' => 'products',
            'path' => 'products',
        ]);
        $child = MediaFolder::query()->create([
            'parent_id' => $folder->id,
            'name' => 'Featured',
            'slug' => 'featured',
            'path' => 'products/featured',
        ]);
        Storage::disk('public')->put('2026/08/product.jpg', 'image');
        $file = $this->mediaFile([
            'folder_id' => $child->id,
            'path' => '2026/08/product.jpg',
        ]);

        $this->actingAs($admin, 'admin')
            ->delete('/admin/media/folders/'.$folder->id)
            ->assertRedirect(route('admin.media.index'));

        $this->assertDatabaseMissing('media_folders', ['id' => $folder->id]);
        $this->assertDatabaseMissing('media_folders', ['id' => $child->id]);
        $this->assertDatabaseMissing('media_files', ['id' => $file->id]);
        Storage::disk('public')->assertMissing('2026/08/product.jpg');
    }

    public function test_media_manager_renders_file_and_folder_action_modals(): void
    {
        $admin = $this->adminUser();
        $folder = MediaFolder::query()->create([
            'name' => 'Products',
            'slug' => 'products',
            'path' => 'products',
        ]);
        $file = $this->mediaFile();

        $this->actingAs($admin, 'admin')
            ->get('/admin/media')
            ->assertOk()
            ->assertSee('data-bs-target="#media-folder-rename-'.$folder->id.'"', false)
            ->assertSee('data-bs-target="#media-folder-move-'.$folder->id.'"', false)
            ->assertSee('data-bs-target="#media-folder-delete-'.$folder->id.'"', false)
            ->assertSee('action="'.route('admin.media.folders.update', $folder, false).'"', false)
            ->assertSee('action="'.route('admin.media.folders.destroy', $folder, false).'"', false)
            ->assertSee('data-bs-target="#media-file-rename-'.$file->id.'"', false)
            ->assertSee('data-bs-target="#media-file-move-'.$file->id.'"', false)
            ->assertSee('data-bs-target="#media-file-delete-'.$file->id.'"', false)
            ->assertSee('action="'.route('admin.media.files.update', $file, false).'"', false)
            ->assertSee('action="'.route('admin.media.files.destroy', $file, false).'"', false)
            ->assertSee('Root library');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function mediaFile(array $attributes = []): MediaFile
    {
        return MediaFile::query()->create(array_merge([
            'folder_id' => null,
            'name' => 'Hero',
            'file_name' => 'hero.jpg',
            'path' => '2026/08/hero.jpg',
            'disk' => 'public',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
        ], $attributes));
    }

    private function adminUser(): User
    {
        return User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
    }
}
