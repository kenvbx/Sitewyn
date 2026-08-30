<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Sitewyn\Core\Base\Models\Permission;
use Sitewyn\Core\Base\Models\Role;
use Sitewyn\Core\Base\Support\AdminMenuRegistry;
use Sitewyn\Core\Base\Support\PermissionRegistry;
use Sitewyn\Packages\Blog\Models\Post;
use Sitewyn\Packages\Blog\Models\Tag;
use Sitewyn\Packages\Page\Models\Page;
use Tests\TestCase;

class AdminTagCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_tags(): void
    {
        $this->get('/admin/tags')
            ->assertRedirect('/admin/login');
    }

    public function test_tag_routes_require_their_permissions(): void
    {
        $user = $this->plainAdmin();
        $tag = $this->createTag(['name' => 'Laravel', 'slug' => 'laravel']);

        $this->actingAs($user, 'admin')
            ->get('/admin/tags')
            ->assertForbidden();

        $this->actingAs($user, 'admin')
            ->get('/admin/tags/create')
            ->assertForbidden();

        $this->actingAs($user, 'admin')
            ->post('/admin/tags', [
                'name' => 'Blocked tag',
            ])
            ->assertForbidden();

        $this->actingAs($user, 'admin')
            ->get('/admin/tags/'.$tag->id.'/edit')
            ->assertForbidden();

        $this->actingAs($user, 'admin')
            ->put('/admin/tags/'.$tag->id, [
                'name' => 'Blocked update',
            ])
            ->assertForbidden();

        $this->actingAs($user, 'admin')
            ->delete('/admin/tags/'.$tag->id)
            ->assertForbidden();
    }

    public function test_tag_permissions_are_registered(): void
    {
        $registry = $this->app->make(PermissionRegistry::class);

        foreach (['tag.index', 'tag.create', 'tag.edit', 'tag.delete'] as $key) {
            $this->assertTrue($registry->has($key));
        }

        $permission = $registry->all()->firstWhere('key', 'tag.index');

        $this->assertSame('tag', $permission['group']);
        $this->assertSame('package/blog', $permission['module']);
    }

    public function test_tags_sidebar_item_requires_tag_index_permission(): void
    {
        $registry = $this->app->make(AdminMenuRegistry::class);

        $this->assertTrue($registry->has('tags'));

        $viewer = $this->userWithPermissions(['tag.index']);

        $this->assertTrue($registry->visibleFor($viewer)->pluck('id')->contains('tags'));
        $this->assertFalse($registry->visibleFor($this->plainAdmin())->pluck('id')->contains('tags'));
    }

    public function test_super_admin_can_view_tags_index_with_sidebar_menu(): void
    {
        $admin = $this->adminUser();
        $laravel = $this->createTag(['name' => 'Laravel', 'slug' => 'laravel']);

        $this->actingAs($admin, 'admin')
            ->get('/admin/tags')
            ->assertOk()
            ->assertSee('navbar-vertical', false)
            ->assertSee('Categories')
            ->assertSee('Tags')
            ->assertSee('Tag list')
            ->assertSee('Laravel')
            ->assertSee(route('admin.tags.edit', $laravel), false)
            ->assertSee('data-bs-target="#tag-delete-'.$laravel->id.'"', false);
    }

    public function test_super_admin_can_store_tag_with_generated_slug(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin, 'admin')
            ->post('/admin/tags', [
                'name' => 'Hello World',
                'slug' => '',
            ])
            ->assertRedirect('/admin/tags')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('tags', [
            'name' => 'Hello World',
            'slug' => 'hello-world',
        ]);
    }

    public function test_super_admin_can_store_tag_with_manual_slug(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin, 'admin')
            ->post('/admin/tags', [
                'name' => 'PHP',
                'slug' => 'php-8',
            ])
            ->assertRedirect('/admin/tags')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('tags', [
            'name' => 'PHP',
            'slug' => 'php-8',
        ]);
    }

    public function test_store_tag_suffixes_duplicate_slug_within_tags_namespace(): void
    {
        $admin = $this->adminUser();
        $this->createTag(['name' => 'PHP', 'slug' => 'php']);

        $this->actingAs($admin, 'admin')
            ->post('/admin/tags', [
                'name' => 'PHP',
            ])
            ->assertRedirect('/admin/tags');

        $this->assertDatabaseHas('tags', [
            'name' => 'PHP',
            'slug' => 'php-2',
        ]);
        $this->assertDatabaseCount('tags', 2);
    }

    public function test_tag_slugs_are_independent_of_the_pages_and_posts_namespace(): void
    {
        $admin = $this->adminUser();
        Page::query()->create([
            'title' => 'PHP',
            'slug' => 'php',
            'status' => Page::STATUS_PUBLISHED,
        ]);
        Post::query()->create([
            'title' => 'PHP',
            'slug' => 'php',
            'status' => Post::STATUS_DRAFT,
        ]);

        $this->actingAs($admin, 'admin')
            ->post('/admin/tags', [
                'name' => 'PHP',
            ])
            ->assertRedirect('/admin/tags');

        // Neither the page nor the post slug forces a suffix on the tag slug.
        $this->assertDatabaseHas('tags', [
            'name' => 'PHP',
            'slug' => 'php',
        ]);
    }

    public function test_super_admin_can_update_tag_keeping_slug(): void
    {
        $admin = $this->adminUser();
        $tag = $this->createTag(['name' => 'Laravel', 'slug' => 'laravel']);

        $this->actingAs($admin, 'admin')
            ->put('/admin/tags/'.$tag->id, [
                'name' => 'Laravel Framework',
                'slug' => '',
            ])
            ->assertRedirect('/admin/tags/'.$tag->id.'/edit')
            ->assertSessionHas('status');

        $tag->refresh();

        $this->assertSame('Laravel Framework', $tag->name);
        $this->assertSame('laravel', $tag->slug);
    }

    public function test_update_tag_suffixes_manual_slug_when_taken(): void
    {
        $admin = $this->adminUser();
        $other = $this->createTag(['name' => 'PHP', 'slug' => 'php']);
        $tag = $this->createTag(['name' => 'Hypertext', 'slug' => 'hypertext']);

        $this->actingAs($admin, 'admin')
            ->put('/admin/tags/'.$tag->id, [
                'name' => 'Hypertext',
                'slug' => 'php',
            ])
            ->assertRedirect('/admin/tags/'.$tag->id.'/edit')
            ->assertSessionHas('status');

        $tag->refresh();

        $this->assertSame('php-2', $tag->slug);
        $this->assertDatabaseHas('tags', [
            'id' => $other->id,
            'slug' => 'php',
        ]);
    }

    public function test_super_admin_can_edit_tag_with_slug_field(): void
    {
        $admin = $this->adminUser();
        $tag = $this->createTag(['name' => 'Laravel', 'slug' => 'laravel']);

        $this->actingAs($admin, 'admin')
            ->get('/admin/tags/'.$tag->id.'/edit')
            ->assertOk()
            ->assertSee('Laravel')
            ->assertSee('value="laravel"', false)
            ->assertSee('Leave blank to keep the current slug.');
    }

    public function test_super_admin_can_delete_tag_detaching_only_that_tag_from_posts(): void
    {
        $admin = $this->adminUser();
        $php = $this->createTag(['name' => 'PHP', 'slug' => 'php']);
        $laravel = $this->createTag(['name' => 'Laravel', 'slug' => 'laravel']);
        $post = $this->createPost();
        $post->tags()->attach([$php->id, $laravel->id]);

        $this->actingAs($admin, 'admin')
            ->delete('/admin/tags/'.$php->id)
            ->assertRedirect('/admin/tags')
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('tags', ['id' => $php->id]);
        $this->assertDatabaseHas('tags', ['id' => $laravel->id]);

        // The pivot row for the deleted tag cascades; the other tag stays.
        $this->assertDatabaseMissing('post_tag', ['tag_id' => $php->id]);
        $this->assertDatabaseHas('post_tag', [
            'post_id' => $post->id,
            'tag_id' => $laravel->id,
        ]);

        $this->assertSame(['Laravel'], $post->refresh()->tags->pluck('name')->all());
    }

    public function test_tags_index_supports_name_search(): void
    {
        $admin = $this->adminUser();
        $laravel = $this->createTag(['name' => 'Laravel', 'slug' => 'laravel']);
        $this->createTag(['name' => 'PHP', 'slug' => 'php']);

        $this->actingAs($admin, 'admin')
            ->get('/admin/tags?q=PHP')
            ->assertOk()
            ->assertSee('PHP')
            // The app name in tests is "Laravel", so assert on the row's
            // edit link instead of the tag name itself.
            ->assertDontSee(route('admin.tags.edit', $laravel), false);
    }

    private function createTag(array $attributes = []): Tag
    {
        return Tag::query()->create([
            'name' => 'Untitled',
            'slug' => 'untitled-'.uniqid(),
            ...$attributes,
        ]);
    }

    private function createPost(array $attributes = []): Post
    {
        return Post::query()->create([
            'title' => 'Untitled',
            'slug' => 'untitled-'.uniqid(),
            'status' => Post::STATUS_DRAFT,
            ...$attributes,
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
                    'module' => 'package/blog',
                    'group' => 'tag',
                    'description' => null,
                ],
            );

            $role->permissions()->attach($permission);
        }

        $user->roles()->attach($role);

        return $user;
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
}
