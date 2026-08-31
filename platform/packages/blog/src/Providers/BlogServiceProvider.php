<?php

namespace Sitewyn\Packages\Blog\Providers;

use Illuminate\Support\ServiceProvider;
use Sitewyn\Core\Base\Observers\AuditObserver;
use Sitewyn\Core\Base\Support\AdminMenuRegistry;
use Sitewyn\Core\Base\Support\PermissionRegistry;
use Sitewyn\Core\Base\Support\SitemapRegistry;
use Sitewyn\Packages\Blog\Models\Category;
use Sitewyn\Packages\Blog\Models\Post;
use Sitewyn\Packages\Blog\Models\Tag;
use Sitewyn\Packages\Blog\Repositories\PostRepository;

class BlogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom($this->modulePath('config/blog.php'), 'blog');
    }

    public function boot(): void
    {
        $this->loadViewsFrom($this->modulePath('resources/views'), 'package/blog');
        $this->loadRoutesFrom($this->modulePath('routes/web.php'));
        $this->loadMigrationsFrom($this->modulePath('database/migrations'));
        $this->registerAuditObserver();
        $this->registerPermissions();
        $this->registerAdminMenu();
        $this->registerSitemapContributor();
    }

    private function modulePath(string $path = ''): string
    {
        $basePath = dirname(__DIR__, 2);

        return $path === '' ? $basePath : $basePath.DIRECTORY_SEPARATOR.$path;
    }

    private function registerAuditObserver(): void
    {
        if (! class_exists(AuditObserver::class)) {
            return;
        }

        Post::observe(AuditObserver::class);
        Category::observe(AuditObserver::class);
        Tag::observe(AuditObserver::class);
    }

    private function registerAdminMenu(): void
    {
        if (! class_exists(AdminMenuRegistry::class)) {
            return;
        }

        $this->app->make(AdminMenuRegistry::class)->add([
            'id' => 'posts',
            'title' => 'Posts',
            'route' => 'admin.posts.index',
            'icon' => 'post',
            'permission' => 'post.index',
            'active' => ['admin.posts.*'],
            'order' => 21,
        ]);

        $this->app->make(AdminMenuRegistry::class)->add([
            'id' => 'categories',
            'title' => 'Categories',
            'route' => 'admin.categories.index',
            'icon' => 'category',
            'permission' => 'category.index',
            'active' => ['admin.categories.*'],
            'order' => 22,
        ]);

        $this->app->make(AdminMenuRegistry::class)->add([
            'id' => 'tags',
            'title' => 'Tags',
            'route' => 'admin.tags.index',
            'icon' => 'tag',
            'permission' => 'tag.index',
            'active' => ['admin.tags.*'],
            'order' => 23,
        ]);
    }

    private function registerSitemapContributor(): void
    {
        if (! class_exists(SitemapRegistry::class)) {
            return;
        }

        // The callable runs lazily on each /sitemap.xml request, so posts
        // published after boot still show up without any cache to clear.
        $this->app->make(SitemapRegistry::class)->register(function (): array {
            return $this->app->make(PostRepository::class)
                ->byStatus(Post::STATUS_PUBLISHED)
                ->map(fn (Post $post): array => [
                    'loc' => url("/blog/{$post->slug}"),
                    'lastmod' => $post->updated_at,
                ])
                ->all();
        });
    }

    private function registerPermissions(): void
    {
        if (! class_exists(PermissionRegistry::class)) {
            return;
        }

        $this->app->make(PermissionRegistry::class)->register([
            [
                'key' => 'post.index',
                'name' => 'View posts',
                'group' => 'post',
                'description' => 'View the admin post list and previews.',
            ],
            [
                'key' => 'post.create',
                'name' => 'Create posts',
                'group' => 'post',
                'description' => 'Create admin posts.',
            ],
            [
                'key' => 'post.edit',
                'name' => 'Edit posts',
                'group' => 'post',
                'description' => 'Edit admin posts.',
            ],
            [
                'key' => 'post.delete',
                'name' => 'Delete posts',
                'group' => 'post',
                'description' => 'Delete admin posts.',
            ],
            [
                'key' => 'category.index',
                'name' => 'View categories',
                'group' => 'category',
                'description' => 'View the admin category list.',
            ],
            [
                'key' => 'category.create',
                'name' => 'Create categories',
                'group' => 'category',
                'description' => 'Create admin categories.',
            ],
            [
                'key' => 'category.edit',
                'name' => 'Edit categories',
                'group' => 'category',
                'description' => 'Edit admin categories.',
            ],
            [
                'key' => 'category.delete',
                'name' => 'Delete categories',
                'group' => 'category',
                'description' => 'Delete admin categories.',
            ],
            [
                'key' => 'tag.index',
                'name' => 'View tags',
                'group' => 'tag',
                'description' => 'View the admin tag list.',
            ],
            [
                'key' => 'tag.create',
                'name' => 'Create tags',
                'group' => 'tag',
                'description' => 'Create admin tags.',
            ],
            [
                'key' => 'tag.edit',
                'name' => 'Edit tags',
                'group' => 'tag',
                'description' => 'Edit admin tags.',
            ],
            [
                'key' => 'tag.delete',
                'name' => 'Delete tags',
                'group' => 'tag',
                'description' => 'Delete admin tags.',
            ],
        ], 'package/blog');
    }
}
