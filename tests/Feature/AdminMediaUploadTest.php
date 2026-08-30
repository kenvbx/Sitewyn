<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Sitewyn\Packages\Media\Models\MediaFolder;
use Sitewyn\Packages\Media\Support\RemoteUrlGuard;
use Tests\Support\FakeDnsResolver;
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

    /**
     * Bind a RemoteUrlGuard with a fake DNS resolver so URL uploads never touch
     * the real network during tests. IP-literal hosts never resolve DNS.
     *
     * @param  array<string, list<string>>  $map
     */
    private function fakeDns(array $map = []): void
    {
        $this->app->singleton(RemoteUrlGuard::class, fn (): RemoteUrlGuard => new RemoteUrlGuard(
            new FakeDnsResolver($map),
        ));
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
        $this->fakeDns(['example.com' => ['93.184.216.34']]);
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
        $this->fakeDns(['example.com' => ['93.184.216.34']]);
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
        $this->fakeDns(['example.com' => ['93.184.216.34']]);
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

    public function test_upload_from_url_rejects_forbidden_hosts_without_sending_requests(): void
    {
        Storage::fake('public');
        Http::fake();
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        foreach (['http://127.0.0.1/x', 'http://169.254.169.254/latest', 'http://10.0.0.5/secret'] as $url) {
            $response = $this->actingAs($admin, 'admin')
                ->post('/admin/media/upload', [
                    'upload_url' => $url,
                ], [
                    'Accept' => 'application/json',
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('upload_urls');

            $this->assertStringContainsString('forbidden host', (string) $response->json('errors.upload_urls.0'));
        }

        Http::assertNothingSent();
        $this->assertDatabaseCount('media_files', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_upload_from_url_rejects_hostname_resolving_to_private_ip(): void
    {
        Storage::fake('public');
        Http::fake();
        $this->fakeDns(['metadata.internal.example' => ['10.0.0.5']]);
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->post('/admin/media/upload', [
                'upload_url' => 'http://metadata.internal.example/credentials',
            ], [
                'Accept' => 'application/json',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('upload_urls');

        Http::assertNothingSent();
        $this->assertDatabaseCount('media_files', 0);
    }

    public function test_upload_from_url_rejects_non_http_schemes(): void
    {
        Storage::fake('public');
        Http::fake();
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        // Host-bearing URLs pass syntax validation and are rejected by the guard.
        foreach (['file://169.254.169.254/latest/meta-data', 'ftp://93.184.216.34/asset.png'] as $url) {
            $response = $this->actingAs($admin, 'admin')
                ->post('/admin/media/upload', [
                    'upload_url' => $url,
                ], [
                    'Accept' => 'application/json',
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('upload_urls');

            $this->assertStringContainsString('forbidden host', (string) $response->json('errors.upload_urls.0'));
        }

        // Host-less URLs (file:///path) are already rejected as invalid URLs.
        $this->actingAs($admin, 'admin')
            ->post('/admin/media/upload', [
                'upload_url' => 'file:///etc/passwd',
            ], [
                'Accept' => 'application/json',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('upload_url');

        Http::assertNothingSent();
        $this->assertDatabaseCount('media_files', 0);
    }

    public function test_upload_from_url_rejects_redirect_to_forbidden_host(): void
    {
        Storage::fake('public');
        Http::fake([
            'https://example.com/redirect.png' => Http::response('', 302, [
                'Location' => 'http://127.0.0.1/secret',
            ]),
        ]);
        $this->fakeDns(['example.com' => ['93.184.216.34']]);
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->post('/admin/media/upload', [
                'upload_url' => 'https://example.com/redirect.png',
            ], [
                'Accept' => 'application/json',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('upload_urls');

        $this->assertStringContainsString('forbidden host', (string) $response->json('errors.upload_urls.0'));
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '127.0.0.1'));
        $this->assertDatabaseCount('media_files', 0);
    }

    public function test_upload_from_url_follows_validated_redirects(): void
    {
        Storage::fake('public');
        Http::fake([
            'https://example.com/moved.png' => Http::response('', 302, [
                'Location' => '/uploads/remote-hero.png',
            ]),
            'https://example.com/uploads/remote-hero.png' => Http::response(
                UploadedFile::fake()->image('remote-hero.png', 640, 480)->getContent(),
                200,
                ['Content-Type' => 'image/png'],
            ),
        ]);
        $this->fakeDns(['example.com' => ['93.184.216.34']]);
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->post('/admin/media/upload', [
                'upload_url' => 'https://example.com/moved.png',
            ], [
                'Accept' => 'application/json',
            ])
            ->assertCreated()
            ->assertJsonCount(1, 'files')
            ->assertJsonPath('files.0.file_name', 'remote-hero.png')
            ->assertJsonPath('files.0.mime_type', 'image/png');

        Storage::disk('public')->assertExists($response->json('files.0.path'));
        $this->assertDatabaseCount('media_files', 1);
    }

    public function test_upload_from_url_rejects_redirect_chains_over_limit(): void
    {
        Storage::fake('public');
        $stubs = [];

        foreach (range(0, 5) as $index) {
            $stubs["https://example.com/loop-{$index}.png"] = Http::response('', 302, [
                'Location' => 'https://example.com/loop-'.($index + 1).'.png',
            ]);
        }

        Http::fake($stubs);
        $this->fakeDns(['example.com' => ['93.184.216.34']]);
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->post('/admin/media/upload', [
                'upload_url' => 'https://example.com/loop-0.png',
            ], [
                'Accept' => 'application/json',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('upload_urls');

        $this->assertStringContainsString('too many redirects', (string) $response->json('errors.upload_urls.0'));
        Http::assertSentCount(6);
        $this->assertDatabaseCount('media_files', 0);
    }

    public function test_upload_from_url_rejects_body_over_max_size_while_streaming(): void
    {
        Storage::fake('public');
        config(['media.max_upload_size' => 1]);
        Http::fake([
            'https://example.com/big.txt' => Http::response(
                str_repeat('A', 2 * 1024 * 1024),
                200,
                ['Content-Type' => 'text/plain'],
            ),
        ]);
        $this->fakeDns(['example.com' => ['93.184.216.34']]);
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->post('/admin/media/upload', [
                'upload_url' => 'https://example.com/big.txt',
            ], [
                'Accept' => 'application/json',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('upload_urls');

        $this->assertStringContainsString('may not be greater than 1 kilobytes', (string) $response->json('errors.upload_urls.0'));
        $this->assertDatabaseCount('media_files', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_upload_from_url_rejects_dangerous_extensions(): void
    {
        Storage::fake('public');
        Http::fake([
            'https://example.com/exploit.html' => Http::response(
                '<script>alert("xss")</script>',
                200,
                ['Content-Type' => 'text/plain'],
            ),
        ]);
        $this->fakeDns(['example.com' => ['93.184.216.34']]);
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->post('/admin/media/upload', [
                'upload_url' => 'https://example.com/exploit.html',
            ], [
                'Accept' => 'application/json',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('upload_urls');

        $this->assertStringContainsString('not an allowed file', (string) $response->json('errors.upload_urls.0'));
        $this->assertDatabaseCount('media_files', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_upload_rejects_svg_files(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->post('/admin/media/upload', [
                'file' => UploadedFile::fake()->createWithContent(
                    'malicious.svg',
                    '<svg xmlns="http://www.w3.org/2000/svg"><script>alert("xss")</script></svg>',
                ),
            ], [
                'Accept' => 'application/json',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');

        $this->assertDatabaseCount('media_files', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_upload_rejects_disguised_html_files(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->post('/admin/media/upload', [
                'file' => UploadedFile::fake()->create('evil.html', 1, 'text/plain'),
            ], [
                'Accept' => 'application/json',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');

        $this->assertDatabaseCount('media_files', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_upload_rejects_php_files_disguised_as_text(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->post('/admin/media/upload', [
                'file' => UploadedFile::fake()->create('shell.php', 1, 'text/plain'),
            ], [
                'Accept' => 'application/json',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');

        $this->assertDatabaseCount('media_files', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_upload_allows_safe_extensions(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->post('/admin/media/upload', [
                'files' => [
                    UploadedFile::fake()->create('notes.txt', 1, 'text/plain'),
                    UploadedFile::fake()->image('photo.png', 300, 200),
                ],
            ], [
                'Accept' => 'application/json',
            ])
            ->assertCreated()
            ->assertJsonCount(2, 'files');

        $this->assertDatabaseCount('media_files', 2);
        Storage::disk('public')->assertExists($response->json('files.0.path'));
        Storage::disk('public')->assertExists($response->json('files.1.path'));
    }
}
