<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Sitewyn\Packages\Media\Models\MediaFolder;
use Tests\TestCase;

class AdminMediaUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_upload_media(): void
    {
        $this->post('/admin/media/upload', [
            'file' => UploadedFile::fake()->image('hero.jpg'),
        ])->assertRedirect('/admin/login');
    }

    public function test_admin_can_upload_single_file(): void
    {
        Storage::fake('public');
        $this->travelTo('2026-08-16 10:00:00');
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $folder = MediaFolder::query()->create([
            'name' => 'Images',
            'slug' => 'images',
            'path' => 'images',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->post('/admin/media/upload', [
                'folder_id' => $folder->id,
                'file' => UploadedFile::fake()->image('Hero Banner.jpg', 1200, 630)->size(512),
            ], [
                'Accept' => 'application/json',
            ])
            ->assertCreated()
            ->assertJsonCount(1, 'files')
            ->assertJsonPath('files.0.folder_id', $folder->id)
            ->assertJsonPath('files.0.name', 'Hero Banner')
            ->assertJsonPath('files.0.file_name', 'Hero Banner.jpg')
            ->assertJsonPath('files.0.disk', 'public')
            ->assertJsonPath('files.0.mime_type', 'image/jpeg')
            ->assertJsonPath('files.0.width', 1200)
            ->assertJsonPath('files.0.height', 630)
            ->assertJsonPath('files.0.conversions.thumb.width', 150)
            ->assertJsonPath('files.0.conversions.thumb.height', 150)
            ->assertJsonPath('files.0.conversions.medium.width', 768)
            ->assertJsonPath('files.0.conversions.medium.height', 403);

        $path = $response->json('files.0.path');
        $thumbPath = $response->json('files.0.conversions.thumb.path');
        $mediumPath = $response->json('files.0.conversions.medium.path');

        $this->assertStringStartsWith('2026/08/hero-banner-', $path);
        $this->assertStringContainsString('/conversions/', $thumbPath);
        $this->assertStringContainsString('/conversions/', $mediumPath);
        Storage::disk('public')->assertExists($path);
        Storage::disk('public')->assertExists($thumbPath);
        Storage::disk('public')->assertExists($mediumPath);
        $this->assertDatabaseHas('media_files', [
            'folder_id' => $folder->id,
            'name' => 'Hero Banner',
            'file_name' => 'Hero Banner.jpg',
            'path' => $path,
            'disk' => 'public',
            'mime_type' => 'image/jpeg',
            'width' => 1200,
            'height' => 630,
        ]);
        $this->assertDatabaseHas('media_files', [
            'path' => $path,
            'conversions' => json_encode($response->json('files.0.conversions')),
        ]);
    }

    public function test_admin_can_upload_multiple_files(): void
    {
        Storage::fake('public');
        $this->travelTo('2026-08-16 10:00:00');
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->post('/admin/media/upload', [
                'files' => [
                    UploadedFile::fake()->image('gallery-one.png', 800, 600),
                    UploadedFile::fake()->create('brief.txt', 2, 'text/plain'),
                ],
            ], [
                'Accept' => 'application/json',
            ])
            ->assertCreated()
            ->assertJsonCount(2, 'files');

        foreach ($response->json('files') as $file) {
            $this->assertStringStartsWith('2026/08/', $file['path']);
            Storage::disk('public')->assertExists($file['path']);
        }

        $this->assertNotEmpty($response->json('files.0.conversions.thumb.path'));
        $this->assertSame([], $response->json('files.1.conversions'));

        $this->assertDatabaseCount('media_files', 2);
    }

    public function test_admin_can_upload_file_from_url(): void
    {
        Storage::fake('public');
        Http::fake([
            'https://example.com/uploads/remote-hero.png' => Http::response(
                UploadedFile::fake()->image('remote-hero.png', 640, 480)->getContent(),
                200,
                ['Content-Type' => 'image/png'],
            ),
        ]);
        $this->travelTo('2026-08-16 10:00:00');
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $folder = MediaFolder::query()->create([
            'name' => 'Remote',
            'slug' => 'remote',
            'path' => 'remote',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->post('/admin/media/upload', [
                'folder_id' => $folder->id,
                'upload_url' => 'https://example.com/uploads/remote-hero.png',
            ], [
                'Accept' => 'application/json',
            ])
            ->assertCreated()
            ->assertJsonCount(1, 'files')
            ->assertJsonPath('files.0.folder_id', $folder->id)
            ->assertJsonPath('files.0.name', 'remote-hero')
            ->assertJsonPath('files.0.file_name', 'remote-hero.png')
            ->assertJsonPath('files.0.mime_type', 'image/png')
            ->assertJsonPath('files.0.width', 640)
            ->assertJsonPath('files.0.height', 480);

        $this->assertStringStartsWith('/storage/', $response->json('files.0.url'));
        $this->assertStringStartsWith('/storage/', $response->json('files.0.conversions.thumb.url'));
        Storage::disk('public')->assertExists($response->json('files.0.path'));
        Storage::disk('public')->assertExists($response->json('files.0.conversions.thumb.path'));
    }

    public function test_admin_can_upload_multiple_files_from_urls(): void
    {
        Storage::fake('public');
        Http::fake([
            'https://example.com/uploads/first.png' => Http::response(
                UploadedFile::fake()->image('first.png', 320, 240)->getContent(),
                200,
                ['Content-Type' => 'image/png'],
            ),
            'https://example.com/uploads/second.jpg' => Http::response(
                UploadedFile::fake()->image('second.jpg', 640, 360)->getContent(),
                200,
                ['Content-Type' => 'image/jpeg'],
            ),
        ]);
        $this->travelTo('2026-08-16 10:00:00');
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->post('/admin/media/upload', [
                'upload_urls' => implode("\n", [
                    'https://example.com/uploads/first.png',
                    'https://example.com/uploads/second.jpg',
                ]),
            ], [
                'Accept' => 'application/json',
            ])
            ->assertCreated()
            ->assertJsonCount(2, 'files')
            ->assertJsonPath('files.0.name', 'first')
            ->assertJsonPath('files.1.name', 'second');

        foreach ($response->json('files') as $file) {
            $this->assertStringStartsWith('/storage/', $file['url']);
            Storage::disk('public')->assertExists($file['path']);
            Storage::disk('public')->assertExists($file['conversions']['thumb']['path']);
        }

        $this->assertDatabaseCount('media_files', 2);
    }

    public function test_upload_from_url_rejects_failed_download(): void
    {
        Storage::fake('public');
        Http::fake([
            'https://example.com/missing.png' => Http::response('', 404),
        ]);
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->post('/admin/media/upload', [
                'upload_url' => 'https://example.com/missing.png',
            ], [
                'Accept' => 'application/json',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('upload_urls');

        $this->assertDatabaseCount('media_files', 0);
    }

    public function test_upload_rejects_invalid_mime_type(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->post('/admin/media/upload', [
                'file' => UploadedFile::fake()->create('shell.php', 1, 'application/x-php'),
            ], [
                'Accept' => 'application/json',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');

        $this->assertDatabaseCount('media_files', 0);
        Storage::disk('public')->assertMissing('shell.php');
    }

    public function test_upload_rejects_file_over_max_size(): void
    {
        Storage::fake('public');
        config(['media.max_upload_size' => 1]);
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->post('/admin/media/upload', [
                'file' => UploadedFile::fake()->create('large.txt', 2, 'text/plain'),
            ], [
                'Accept' => 'application/json',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');

        $this->assertDatabaseCount('media_files', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }
}
