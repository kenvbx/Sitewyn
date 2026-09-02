<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Sitewyn\Core\Base\Models\Permission;
use Sitewyn\Core\Base\Models\Role;
use Sitewyn\Core\Base\Support\AdminMenuRegistry;
use Sitewyn\Core\Base\Support\BackupService;
use Sitewyn\Packages\Blog\Models\Post;
use Sitewyn\Packages\Page\Models\Page;
use Tests\TestCase;
use ZipArchive;

class AdminBackupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Backups read/write the real local and media disks through
        // Storage::disk(), so swapping both for fakes isolates every test.
        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_create_builds_archive_with_database_dump_and_media_mirror(): void
    {
        $this->actingAs($this->adminUser(), 'admin');

        Page::query()->create([
            'title' => 'About us',
            'slug' => 'about-us',
            'status' => Page::STATUS_DRAFT,
        ]);
        Storage::disk('public')->put('2026/08/hero.txt', 'hero body');

        $name = $this->backups()->create();

        $this->assertMatchesRegularExpression('/^backup-[0-9-]+\.zip$/', $name);
        $this->assertTrue(Storage::disk('local')->exists('backups/'.$name));

        $zip = new ZipArchive;
        $this->assertTrue($zip->open(Storage::disk('local')->path('backups/'.$name)) === true);

        $this->assertNotFalse($zip->locateName('database.json'));
        $this->assertNotFalse($zip->locateName('files/2026/08/hero.txt'));

        $dump = json_decode((string) $zip->getFromName('database.json'), true);

        $this->assertSame('About us', $dump['pages'][0]['title']);
        $this->assertArrayNotHasKey('migrations', $dump, 'Schema is owned by migrations and never dumped.');

        $zip->close();
    }

    public function test_backup_route_uses_system_url_prefix(): void
    {
        $this->assertTrue(Route::has('admin.system.backups.index'));
        $this->assertSame('/admin/system/backups', route('admin.system.backups.index', [], false));
        $this->assertFalse(Route::has('admin.backups.index'));
    }

    public function test_restore_roundtrip_returns_database_and_media_to_backup_state(): void
    {
        $this->actingAs($this->adminUser(), 'admin');

        Page::query()->create([
            'title' => 'Page one',
            'slug' => 'page-one',
            'status' => Page::STATUS_DRAFT,
        ]);
        Page::query()->create([
            'title' => 'Page two',
            'slug' => 'page-two',
            'status' => Page::STATUS_PUBLISHED,
        ]);
        Post::query()->create([
            'title' => 'Post one',
            'slug' => 'post-one',
            'status' => Post::STATUS_DRAFT,
        ]);
        Storage::disk('public')->put('2026/08/hero.txt', 'hero body');

        $name = $this->backups()->create();

        // Drift away from the backed-up state.
        Page::query()->where('slug', 'page-two')->firstOrFail()->delete();
        Post::query()->where('slug', 'post-one')->firstOrFail()->update(['title' => 'Post one edited']);
        Storage::disk('public')->delete('2026/08/hero.txt');
        Storage::disk('public')->put('2026/08/extra.txt', 'added after backup');

        $this->assertDatabaseCount('pages', 1);

        $this->backups()->restore($name);

        $this->assertDatabaseCount('pages', 2);
        $this->assertDatabaseHas('pages', ['slug' => 'page-two', 'status' => Page::STATUS_PUBLISHED]);
        $this->assertDatabaseHas('posts', ['slug' => 'post-one', 'title' => 'Post one']);
        $this->assertSame('hero body', Storage::disk('public')->get('2026/08/hero.txt'));
        $this->assertFalse(Storage::disk('public')->exists('2026/08/extra.txt'), 'Restore is a full media snapshot: post-backup files are removed.');
    }

    public function test_restore_skips_unknown_dump_tables_and_keeps_other_tables(): void
    {
        $this->actingAs($this->adminUser(), 'admin');

        $usersBefore = DB::table('users')->count();

        $name = 'backup-custom.zip';
        Storage::disk('local')->makeDirectory('backups');
        $zip = new ZipArchive;
        $this->assertTrue($zip->open(Storage::disk('local')->path('backups/'.$name), ZipArchive::CREATE | ZipArchive::OVERWRITE) === true);
        $zip->addFromString('database.json', (string) json_encode([
            'pages' => [
                [
                    'id' => 1,
                    'title' => 'Imported',
                    'slug' => 'imported',
                    'status' => 'draft',
                    'created_at' => '2026-08-30 10:00:00',
                    'updated_at' => '2026-08-30 10:00:00',
                ],
            ],
            // A table recorded by an older install that no longer migrates up.
            'ghost_table' => [['id' => 1, 'name' => 'ghost']],
        ]));
        $zip->close();

        $this->backups()->restore($name);

        $this->assertDatabaseHas('pages', ['slug' => 'imported']);
        $this->assertSame($usersBefore, DB::table('users')->count(), 'Tables absent from the dump are left untouched.');
    }

    public function test_restore_action_flashes_error_for_corrupt_archive(): void
    {
        $this->actingAs($this->adminUser(), 'admin');

        Storage::disk('local')->put('backups/backup-broken.zip', 'definitely not a zip archive');

        $this->post('/admin/system/backups/backup-broken.zip/restore')
            ->assertRedirect('/admin/system/backups')
            ->assertSessionHas('error');

        $this->assertTrue(Storage::disk('local')->exists('backups/backup-broken.zip'));
    }

    public function test_download_streams_the_backup_archive(): void
    {
        $this->actingAs($this->adminUser(), 'admin');

        $name = $this->backups()->create();

        $response = $this->get("/admin/system/backups/{$name}/download")->assertOk();

        $this->assertStringContainsString('attachment; filename='.$name, (string) $response->headers->get('content-disposition'));
        $response->assertDownload($name);
        // BinaryFileResponse::getContent() is false until the response is sent,
        // so the archive bytes are verified against the stored file instead.
        $this->assertStringStartsWith('PK', (string) file_get_contents(Storage::disk('local')->path('backups/'.$name)));
    }

    public function test_download_database_backup_streams_database_json(): void
    {
        $this->actingAs($this->adminUser(), 'admin');

        Page::query()->create([
            'title' => 'About us',
            'slug' => 'about-us',
            'status' => Page::STATUS_DRAFT,
        ]);

        $name = $this->backups()->create();
        $response = $this->get("/admin/system/backups/{$name}/download-database")->assertOk();

        $this->assertStringContainsString(pathinfo($name, PATHINFO_FILENAME).'-database.json', (string) $response->headers->get('content-disposition'));
        $this->assertStringContainsString('"pages"', $response->getContent());
        $this->assertStringContainsString('About us', $response->getContent());
    }

    public function test_download_uploads_backup_streams_uploads_only_archive(): void
    {
        $this->actingAs($this->adminUser(), 'admin');

        Page::query()->create([
            'title' => 'About us',
            'slug' => 'about-us',
            'status' => Page::STATUS_DRAFT,
        ]);
        Storage::disk('public')->put('2026/08/hero.txt', 'hero body');

        $name = $this->backups()->create();
        $response = $this->get("/admin/system/backups/{$name}/download-uploads")->assertOk();
        $uploadsName = pathinfo($name, PATHINFO_FILENAME).'-uploads.zip';

        $response->assertDownload($uploadsName);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open(Storage::disk('local')->path('backups/exports/'.$uploadsName)) === true);
        $this->assertNotFalse($zip->locateName('2026/08/hero.txt'));
        $this->assertFalse($zip->locateName('database.json'));
        $zip->close();
    }

    public function test_delete_removes_the_backup_file(): void
    {
        $this->actingAs($this->adminUser(), 'admin');

        $name = $this->backups()->create();

        $this->post("/admin/system/backups/{$name}/delete")
            ->assertRedirect('/admin/system/backups')
            ->assertSessionHas('status');

        $this->assertFalse(Storage::disk('local')->exists('backups/'.$name));
        $this->get("/admin/system/backups/{$name}/download")->assertNotFound();
    }

    public function test_invalid_backup_names_are_rejected(): void
    {
        $this->actingAs($this->adminUser(), 'admin');

        // Anything outside ^backup-[A-Za-z0-9_-]+\.zip$ must 404, so neither
        // the route parameter nor the archive can reach other files.
        $this->get('/admin/system/backups/.env/download')->assertNotFound();
        $this->get('/admin/system/backups/backup-secret.zip/download')->assertNotFound();
        $this->get('/admin/system/backups/backup-secret.zip/download-database')->assertNotFound();
        $this->get('/admin/system/backups/backup-secret.zip/download-uploads')->assertNotFound();
        $this->post('/admin/system/backups/backup-secret.zip/restore')->assertNotFound();
        $this->post('/admin/system/backups/backup-secret.zip/delete')->assertNotFound();

        $traversal = $this->get('/admin/system/backups/..%2F..%2F.env/download');
        $traversal->assertNotFound();
        $this->assertStringNotContainsString('APP_KEY', $traversal->getContent());

        $this->expectException(InvalidArgumentException::class);
        $this->backups()->download('../.env');
    }

    public function test_guest_is_redirected_from_backup_routes(): void
    {
        $this->get('/admin/system/backups')->assertRedirect('/admin/login');
        $this->post('/admin/system/backups')->assertRedirect('/admin/login');
        $this->get('/admin/system/backups/backup-2026-08-30-184500.zip/download')->assertRedirect('/admin/login');
        $this->get('/admin/system/backups/backup-2026-08-30-184500.zip/download-database')->assertRedirect('/admin/login');
        $this->get('/admin/system/backups/backup-2026-08-30-184500.zip/download-uploads')->assertRedirect('/admin/login');
        $this->post('/admin/system/backups/backup-2026-08-30-184500.zip/restore')->assertRedirect('/admin/login');
        $this->post('/admin/system/backups/backup-2026-08-30-184500.zip/delete')->assertRedirect('/admin/login');
    }

    public function test_backup_routes_require_backups_manage_permission(): void
    {
        $plain = $this->plainAdmin();

        $this->actingAs($plain, 'admin')->get('/admin/system/backups')->assertForbidden();
        $this->actingAs($plain, 'admin')->post('/admin/system/backups')->assertForbidden();
        $this->actingAs($plain, 'admin')->get('/admin/system/backups/backup-2026-08-30-184500.zip/download')->assertForbidden();
        $this->actingAs($plain, 'admin')->get('/admin/system/backups/backup-2026-08-30-184500.zip/download-database')->assertForbidden();
        $this->actingAs($plain, 'admin')->get('/admin/system/backups/backup-2026-08-30-184500.zip/download-uploads')->assertForbidden();
        $this->actingAs($plain, 'admin')->post('/admin/system/backups/backup-2026-08-30-184500.zip/restore')->assertForbidden();
        $this->actingAs($plain, 'admin')->post('/admin/system/backups/backup-2026-08-30-184500.zip/delete')->assertForbidden();

        $this->actingAs($this->userWithPermissions(['backups.manage']), 'admin')
            ->get('/admin/system/backups')
            ->assertOk();
    }

    public function test_create_action_flashes_success_and_persists_archive(): void
    {
        $this->actingAs($this->userWithPermissions(['backups.manage']), 'admin');

        $this->post('/admin/system/backups')
            ->assertRedirect('/admin/system/backups')
            ->assertSessionHas('status');

        $files = Storage::disk('local')->files('backups');

        $this->assertCount(1, $files);
        $this->assertStringStartsWith('backup-', basename($files[0]));
    }

    public function test_index_lists_backups_for_permitted_user(): void
    {
        $viewer = $this->userWithPermissions(['backups.manage']);
        $this->actingAs($viewer, 'admin');

        $this->get('/admin/system/backups')->assertOk()->assertSee('No backups yet');

        $name = $this->backups()->create();

        $this->get('/admin/system/backups')
            ->assertOk()
            ->assertSee($name)
            ->assertSee('Generate backup')
            ->assertSee('Description')
            ->assertSee('Download database backup')
            ->assertSee("Download backup of 'uploads' folder", false)
            ->assertSee('Restore this backup')
            ->assertSee('This simple backup feature is ideal for website having less than 1GB of data.')
            ->assertSee('only uploaded files and database are included');
    }

    public function test_backups_are_not_in_sidebar_but_platform_card_requires_backups_manage_permission(): void
    {
        $registry = $this->app->make(AdminMenuRegistry::class);

        $this->assertFalse($registry->has('backups'));

        $viewer = $this->userWithPermissions(['backups.manage']);

        $this->assertFalse($registry->visibleFor($viewer)->pluck('id')->contains('backups'));
        $this->assertFalse($registry->visibleFor($this->plainAdmin())->pluck('id')->contains('backups'));

        $this->actingAs($viewer, 'admin')
            ->get('/admin/system')
            ->assertOk()
            ->assertSee('href="/admin/system/backups"', false)
            ->assertSee('Backup database and uploads folder.');
    }

    private function backups(): BackupService
    {
        return $this->app->make(BackupService::class);
    }

    private function adminUser(): User
    {
        return User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
    }

    private function plainAdmin(): User
    {
        return User::factory()->create([
            'is_super_admin' => false,
            'is_active' => true,
        ]);
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
                    'module' => 'core/base',
                    'group' => 'backups',
                    'description' => null,
                ],
            );

            $role->permissions()->attach($permission);
        }

        $user->roles()->attach($role);

        return $user;
    }
}
