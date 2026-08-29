<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Sitewyn\Packages\Media\Models\MediaFile;
use Sitewyn\Packages\Media\Support\MediaFilePayload;
use Sitewyn\Packages\Media\Support\MediaStorage;
use Tests\TestCase;

class MediaStorageTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_local_disk_uses_relative_storage_url(): void
    {
        config([
            'filesystems.disks.public.driver' => 'local',
            'filesystems.disks.public.root' => storage_path('app/public'),
        ]);

        $this->assertSame('/storage/2026/08/hero.jpg', app(MediaStorage::class)->url('public', '2026/08/hero.jpg'));
    }

    public function test_custom_media_disk_uses_filesystem_url(): void
    {
        config([
            'filesystems.disks.media_cdn' => [
                'driver' => 'local',
                'root' => storage_path('framework/testing/disks/media-cdn'),
                'url' => 'https://cdn.example.com/media',
                'visibility' => 'public',
            ],
        ]);

        $file = MediaFile::query()->create([
            'name' => 'Hero',
            'file_name' => 'hero.jpg',
            'path' => 'custom/hero.jpg',
            'disk' => 'media_cdn',
            'mime_type' => 'image/jpeg',
            'size' => 100,
            'conversions' => [
                'thumb' => [
                    'disk' => 'media_cdn',
                    'path' => 'custom/conversions/hero-thumb.jpg',
                ],
            ],
        ]);

        $payload = MediaFilePayload::make($file);

        $this->assertSame('https://cdn.example.com/media/custom/hero.jpg', $payload['url']);
        $this->assertSame('https://cdn.example.com/media/custom/conversions/hero-thumb.jpg', $payload['thumbnail']);
    }

    public function test_upload_uses_configured_media_disk_and_directory_format(): void
    {
        config([
            'media.disk' => 'media_uploads',
            'media.upload_directory_format' => 'Y',
        ]);
        Storage::fake('media_uploads');
        $this->travelTo('2026-08-29 10:00:00');
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->post('/admin/media/upload', [
                'file' => UploadedFile::fake()->create('report.txt', 1, 'text/plain'),
            ], [
                'Accept' => 'application/json',
            ])
            ->assertCreated()
            ->assertJsonPath('files.0.disk', 'media_uploads');

        $path = $response->json('files.0.path');

        $this->assertStringStartsWith('2026/report-', $path);
        Storage::disk('media_uploads')->assertExists($path);
        $this->assertDatabaseHas('media_files', [
            'disk' => 'media_uploads',
            'path' => $path,
        ]);
    }
}
