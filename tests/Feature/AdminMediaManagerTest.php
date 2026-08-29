<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Sitewyn\Packages\Media\Models\MediaFile;
use Sitewyn\Packages\Media\Models\MediaFolder;
use Tests\TestCase;

class AdminMediaManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_media_manager(): void
    {
        $this->get('/admin/media')
            ->assertRedirect('/admin/login');
    }

    public function test_admin_can_view_media_grid_with_folders_files_and_sidebar_menu(): void
    {
        Storage::fake('public');

        $admin = $this->adminUser();
        $folder = MediaFolder::query()->create([
            'name' => 'Hero Images',
            'slug' => 'hero-images',
            'path' => 'hero-images',
        ]);
        MediaFile::query()->create([
            'folder_id' => null,
            'name' => 'Homepage Banner',
            'file_name' => 'homepage-banner.jpg',
            'path' => '2026/08/homepage-banner.jpg',
            'disk' => 'public',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'width' => 1200,
            'height' => 630,
            'conversions' => [
                'thumb' => [
                    'url' => '/storage/2026/08/conversions/homepage-banner-thumb.jpg',
                ],
            ],
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/media')
            ->assertOk()
            ->assertSee('navbar-vertical', false)
            ->assertSee('Media library')
            ->assertSee('New folder')
            ->assertSee('Hero Images')
            ->assertSee(route('admin.media.index', ['folder' => $folder->id]), false)
            ->assertSee('Homepage Banner')
            ->assertSee('/storage/2026/08/conversions/homepage-banner-thumb.jpg', false)
            ->assertSee('1200x630')
            ->assertSee('Media');
    }

    public function test_media_manager_renders_upload_modal_with_local_and_url_tabs(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin, 'admin')
            ->get('/admin/media')
            ->assertOk()
            ->assertSee('vendor/tabler/dist/libs/dropzone/dist/dropzone.css', false)
            ->assertSee('vendor/tabler/dist/libs/dropzone/dist/dropzone-min.js', false)
            ->assertSee('data-bs-target="#media-upload-modal"', false)
            ->assertSee('Upload from local')
            ->assertSee('Upload from URL')
            ->assertSee('class="dropzone"', false)
            ->assertSee('id="media-dropzone"', false)
            ->assertSee('action="'.route('admin.media.upload', [], false).'"', false)
            ->assertSee('id="media-url-upload-form"', false)
            ->assertSee('name="upload_urls"', false)
            ->assertSee('Enter one URL per line.')
            ->assertSee('Download')
            ->assertSee('data-folder-id=""', false)
            ->assertSee('Dropzone.autoDiscover = false', false)
            ->assertSee('window.tabler_dropzone[dropzoneId] = instance', false)
            ->assertSee('window.location.reload()', false);
    }

    public function test_admin_can_browse_nested_folder_with_breadcrumbs(): void
    {
        $admin = $this->adminUser();
        $root = MediaFolder::query()->create([
            'name' => 'Uploads',
            'slug' => 'uploads',
            'path' => 'uploads',
        ]);
        $child = MediaFolder::query()->create([
            'parent_id' => $root->id,
            'name' => 'Products',
            'slug' => 'products',
            'path' => 'uploads/products',
        ]);
        MediaFile::query()->create([
            'folder_id' => $child->id,
            'name' => 'Spec Sheet',
            'file_name' => 'spec-sheet.pdf',
            'path' => '2026/08/spec-sheet.pdf',
            'disk' => 'public',
            'mime_type' => 'application/pdf',
            'size' => 2048,
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/media?folder='.$child->id)
            ->assertOk()
            ->assertSee('Uploads')
            ->assertSee('Products')
            ->assertSee('uploads/products')
            ->assertSee('Spec Sheet')
            ->assertSee('application/pdf')
            ->assertSee('value="'.$child->id.'"', false)
            ->assertSee('data-folder-id="'.$child->id.'"', false)
            ->assertSee('name="folder_id"', false);
    }

    public function test_admin_can_create_folder_in_current_folder(): void
    {
        $admin = $this->adminUser();
        $parent = MediaFolder::query()->create([
            'name' => 'Uploads',
            'slug' => 'uploads',
            'path' => 'uploads',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->post('/admin/media/folders', [
                'parent_id' => $parent->id,
                'name' => 'Product Photos',
            ]);

        $folder = MediaFolder::query()->where('name', 'Product Photos')->firstOrFail();

        $response->assertRedirect(route('admin.media.index', ['folder' => $folder->id]));
        $this->assertSame($parent->id, $folder->parent_id);
        $this->assertSame('product-photos', $folder->slug);
        $this->assertSame('uploads/product-photos', $folder->path);
    }

    public function test_media_manager_searches_current_folder(): void
    {
        $admin = $this->adminUser();
        $folder = MediaFolder::query()->create([
            'name' => 'Images',
            'slug' => 'images',
            'path' => 'images',
        ]);
        MediaFolder::query()->create([
            'parent_id' => $folder->id,
            'name' => 'Product Photos',
            'slug' => 'product-photos',
            'path' => 'images/product-photos',
        ]);
        MediaFolder::query()->create([
            'parent_id' => $folder->id,
            'name' => 'Documents',
            'slug' => 'documents',
            'path' => 'images/documents',
        ]);
        MediaFile::query()->create([
            'folder_id' => $folder->id,
            'name' => 'Product Hero',
            'file_name' => 'product-hero.jpg',
            'path' => '2026/08/product-hero.jpg',
            'disk' => 'public',
            'mime_type' => 'image/jpeg',
            'size' => 512,
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/media?folder='.$folder->id.'&q=Product')
            ->assertOk()
            ->assertSee('Search: Product')
            ->assertSee('Product Photos')
            ->assertSee('Product Hero')
            ->assertDontSee('Documents');
    }

    private function adminUser(): User
    {
        return User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
    }
}
