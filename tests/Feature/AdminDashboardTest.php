<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Sitewyn\Core\Base\Models\AuditLog;
use Sitewyn\Core\Base\Support\PluginManager;
use Sitewyn\Core\Base\Support\ThemeManager;
use Sitewyn\Packages\Blog\Models\Post;
use Sitewyn\Packages\Page\Models\Page;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_sees_the_dashboard_with_stat_counts(): void
    {
        Page::query()->create(['title' => 'About us', 'slug' => 'about-us', 'status' => Page::STATUS_PUBLISHED]);
        Page::query()->create(['title' => 'Contact', 'slug' => 'contact', 'status' => Page::STATUS_PUBLISHED]);

        $view = $this->actingAs($this->adminUser(), 'admin')->get('/admin');

        $view->assertOk()
            ->assertSee('data-stat="themes">'.app(ThemeManager::class)->all()->count().'<', false)
            ->assertSee('data-stat="users">1<', false)
            ->assertSee('data-stat="plugins">'.count(app(PluginManager::class)->availableSlugs()).'<', false)
            ->assertSee('data-stat="pages">2<', false)
            ->assertSee('Site Analytics')
            ->assertSee('Most Visited Pages')
            ->assertSee('Top Browsers')
            ->assertSee('Top Referrers')
            ->assertSee('Recent Posts')
            ->assertSee('Activity Logs')
            ->assertSee('Request Errors');
    }

    public function test_analytics_widgets_render_seeded_visits(): void
    {
        Page::query()->create(['title' => 'About us', 'slug' => 'about-us', 'status' => Page::STATUS_PUBLISHED]);

        // Two sessions, three pageviews: session A visits two paths, session
        // B one — so the bounce rate is 50.0%.
        $chrome = 'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
        $safari = 'Mozilla/5.0 (Macintosh) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Safari/605.1.15';

        DB::table('analytics_visits')->insert([
            ['path' => '/', 'referer' => null, 'user_agent' => $chrome, 'ip' => '1.1.1.1', 'session_id' => 'sess-a', 'created_at' => now()],
            ['path' => 'about-us', 'referer' => 'https://google.com/search?q=cms', 'user_agent' => $chrome, 'ip' => '1.1.1.1', 'session_id' => 'sess-a', 'created_at' => now()],
            ['path' => '/', 'referer' => null, 'user_agent' => $safari, 'ip' => '2.2.2.2', 'session_id' => 'sess-b', 'created_at' => now()],
        ]);

        $content = $this->actingAs($this->adminUser(), 'admin')->get('/admin')->getContent();

        $this->assertStringContainsString('data-mini="sessions">2<', $content);
        $this->assertStringContainsString('data-mini="visitors">2<', $content);
        $this->assertStringContainsString('data-mini="pageviews">3<', $content);
        $this->assertStringContainsString('data-mini="bounce">50.0<', $content);

        // Most visited: the home path (2 views) outranks the about page (1).
        $homeRow = strpos($content, 'href="/" class="text-reset">/</a>');
        $aboutRow = strpos($content, 'href="/about-us"');
        $this->assertNotFalse($homeRow);
        $this->assertNotFalse($aboutRow);
        $this->assertLessThan($aboutRow, $homeRow);

        // Browsers and referrers.
        $this->assertStringContainsString('>Chrome</td>', $content);
        $this->assertStringContainsString('>Safari</td>', $content);
        $this->assertStringContainsString('(direct)', $content);
        $this->assertStringContainsString('google.com', $content);
    }

    public function test_period_parameter_filters_the_analytics_window(): void
    {
        // A visit from yesterday belongs to the 7d window but not to "today".
        DB::table('analytics_visits')->insert([
            ['path' => 'old-page', 'referer' => null, 'user_agent' => null, 'ip' => '9.9.9.9', 'session_id' => 'sess-old', 'created_at' => now()->subDay()],
        ]);

        $today = $this->actingAs($this->adminUser(), 'admin')->get('/admin')->getContent();
        $this->assertStringNotContainsString('old-page', $today);

        $week = $this->actingAs($this->adminUser(), 'admin')->get('/admin?period=7d')->getContent();
        $this->assertStringContainsString('old-page', $week);

        // An unknown period falls back to "today" and stays a 200.
        $this->actingAs($this->adminUser(), 'admin')->get('/admin?period=fortnight')->assertOk();
    }

    public function test_recent_posts_widget_lists_the_latest_posts(): void
    {
        Post::query()->create([
            'title' => 'Hello world',
            'slug' => 'hello-world',
            'content' => '<p>First post</p>',
            'status' => Post::STATUS_PUBLISHED,
        ]);

        $this->actingAs($this->adminUser(), 'admin')
            ->get('/admin')
            ->assertOk()
            ->assertSee('Hello world')
            ->assertSee('/blog/hello-world', false);
    }

    public function test_activity_logs_widget_lists_recent_admin_actions(): void
    {
        $admin = $this->adminUser();

        // Drop the audit entry the factory-created user account itself wrote.
        AuditLog::query()->delete();

        AuditLog::query()->create([
            'user_id' => $admin->id,
            'action' => 'created',
            'subject_type' => Page::class,
            'subject_id' => 1,
            'ip_address' => '3.3.3.3',
        ]);

        $content = $this->actingAs($admin, 'admin')->get('/admin')->getContent();

        $this->assertStringContainsString($admin->name, $content);
        $this->assertStringContainsString('data-role-badge>admin</span>', $content);
        $this->assertStringContainsString('created Page #1', $content);
        $this->assertStringContainsString('3.3.3.3', $content);
        $this->assertStringContainsString('ago', $content);
        $this->assertStringContainsString('Showing 1 to 1 of 1 records', $content);
    }

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_plain_admin_user_can_view_dashboard(): void
    {
        $this->actingAs($this->plainAdmin(), 'admin')
            ->get('/admin')
            ->assertOk();
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
