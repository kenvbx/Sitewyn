<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Sitewyn\Packages\Media\Models\MediaFile;
use Sitewyn\Packages\Media\Models\MediaFolder;
use Tests\TestCase;

class AdminMediaPickerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_media_picker_endpoint(): void
    {
        $this->get('/admin/media/picker')
            ->assertRedirect('/admin/login');
    }

    public function test_admin_can_load_media_picker_payload(): void
    {
        $admin = $this->adminUser();
        $folder = MediaFolder::query()->create([
            'name' => 'Products',
            'slug' => 'products',
            'path' => 'products',
        ]);
        MediaFile::query()->create([
            'folder_id' => null,
            'name' => 'Hero Banner',
            'file_name' => 'hero-banner.jpg',
            'path' => '2026/08/hero-banner.jpg',
            'disk' => 'public',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'width' => 1200,
            'height' => 630,
            'conversions' => [
                'thumb' => [
                    'path' => '2026/08/conversions/hero-banner-thumb.jpg',
                    'disk' => 'public',
                    'url' => 'http://localhost:8000/storage/2026/08/conversions/hero-banner-thumb.jpg',
                ],
            ],
        ]);

        $this->actingAs($admin, 'admin')
            ->getJson('/admin/media/picker')
            ->assertOk()
            ->assertJsonPath('current_folder', null)
            ->assertJsonPath('folders.0.id', $folder->id)
            ->assertJsonPath('folders.0.name', 'Products')
            ->assertJsonPath('files.0.name', 'Hero Banner')
            ->assertJsonPath('files.0.url', '/storage/2026/08/hero-banner.jpg')
            ->assertJsonPath('files.0.thumbnail', '/storage/2026/08/conversions/hero-banner-thumb.jpg')
            ->assertJsonPath('files.0.dimensions', '1200x630')
            ->assertJsonPath('files.0.is_image', true);
    }

    public function test_media_picker_payload_can_browse_and_search_current_folder(): void
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
            'name' => 'Product Hero',
            'file_name' => 'product-hero.jpg',
            'path' => '2026/08/product-hero.jpg',
            'disk' => 'public',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
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
            ->getJson('/admin/media/picker?folder='.$child->id.'&q=Product')
            ->assertOk()
            ->assertJsonPath('current_folder.id', $child->id)
            ->assertJsonPath('breadcrumbs.0.id', $root->id)
            ->assertJsonPath('breadcrumbs.1.id', $child->id)
            ->assertJsonCount(1, 'files')
            ->assertJsonPath('files.0.name', 'Product Hero');
    }

    public function test_media_picker_component_renders_reusable_field_and_modal(): void
    {
        $admin = $this->adminUser();

        $html = $this->actingAs($admin, 'admin')
            ->withoutVite()
            ->blade('<x-media-picker name="featured_image_id" label="Featured image" value="15" url-value="/storage/hero.jpg" />');

        $html->assertSee('Featured image')
            ->assertSee('name="featured_image_id"', false)
            ->assertSee('value="15"', false)
            ->assertSee('name="featured_image_id_url"', false)
            ->assertSee('value="/storage/hero.jpg"', false)
            ->assertSee('data-media-picker-endpoint="'.route('admin.media.picker', [], false).'"', false)
            ->assertSee('Select media')
            ->assertSee('Use selected media')
            ->assertSee('data-media-picker-grid', false)
            ->assertSee('data-media-picker-id-input', false);
    }

    private function adminUser(): User
    {
        return User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
    }
}
