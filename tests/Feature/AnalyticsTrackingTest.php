<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AnalyticsTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_frontend_get_request_is_tracked_with_path_session_and_ip(): void
    {
        $this->get('/')->assertOk();

        $this->assertDatabaseHas('analytics_visits', [
            'path' => '/',
        ]);

        $visit = DB::table('analytics_visits')->firstOrFail();

        $this->assertNotNull($visit->session_id);
        $this->assertNotNull($visit->ip);
    }

    public function test_admin_login_page_is_not_tracked(): void
    {
        $this->get('/admin/login')->assertOk();

        $this->assertDatabaseCount('analytics_visits', 0);
    }

    public function test_public_seo_files_are_not_tracked(): void
    {
        $this->get('/sitemap.xml')->assertOk();
        $this->get('/robots.txt')->assertOk();

        $this->assertDatabaseCount('analytics_visits', 0);
    }

    public function test_bot_user_agent_is_not_tracked(): void
    {
        $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        ])->get('/')->assertOk();

        $this->assertDatabaseCount('analytics_visits', 0);
    }

    public function test_error_responses_are_not_tracked(): void
    {
        $this->get('/this-page-does-not-exist')->assertNotFound();

        $this->assertDatabaseCount('analytics_visits', 0);
    }

    public function test_post_requests_are_not_tracked(): void
    {
        $this->post('/')->assertStatus(405);

        $this->assertDatabaseCount('analytics_visits', 0);
    }
}
