<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_page_is_visible(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Sign in');
    }

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get('/admin')
            ->assertRedirect('/admin/login');
    }

    public function test_admin_can_login_and_logout(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('secret-password'),
            'is_active' => true,
        ]);

        $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'secret-password',
        ])->assertRedirect('/admin');

        $this->assertAuthenticatedAs($user, 'admin');
        $this->assertNotNull($user->fresh()->last_login_at);

        $this->get('/admin')
            ->assertOk()
            ->assertSee('navbar-vertical', false)
            ->assertSee('Dashboard')
            ->assertDontSee('Combo layout')
            ->assertSee('admin@example.com');

        $this->post('/admin/logout')
            ->assertRedirect('/admin/login');

        $this->assertGuest('admin');
    }

    public function test_admin_login_rejects_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('secret-password'),
            'is_active' => true,
        ]);

        $this->from('/admin/login')
            ->post('/admin/login', [
                'email' => 'admin@example.com',
                'password' => 'wrong-password',
            ])
            ->assertRedirect('/admin/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest('admin');
    }

    public function test_admin_login_rejects_inactive_account(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('secret-password'),
            'is_active' => false,
        ]);

        $this->from('/admin/login')
            ->post('/admin/login', [
                'email' => 'admin@example.com',
                'password' => 'secret-password',
            ])
            ->assertRedirect('/admin/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest('admin');
    }

    public function test_authenticated_admin_is_redirected_away_from_login_page(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $this->actingAs($user, 'admin')
            ->get('/admin/login')
            ->assertRedirect('/admin');
    }

    public function test_admin_login_is_rate_limited_after_five_failed_attempts(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('secret-password'),
            'is_active' => true,
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->from('/admin/login')
                ->post('/admin/login', [
                    'email' => 'admin@example.com',
                    'password' => 'wrong-password',
                ])
                ->assertRedirect('/admin/login')
                ->assertSessionHasErrors('email');
        }

        $this->from('/admin/login')
            ->post('/admin/login', [
                'email' => 'admin@example.com',
                'password' => 'wrong-password',
            ])
            ->assertRedirect('/admin/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest('admin');
        $this->assertStringContainsString(
            'Too many',
            (string) session('errors')->first('email'),
        );
    }

    public function test_correct_password_cannot_bypass_the_login_rate_limit(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('secret-password'),
            'is_active' => true,
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/login', [
                'email' => 'admin@example.com',
                'password' => 'wrong-password',
            ]);
        }

        $this->from('/admin/login')
            ->post('/admin/login', [
                'email' => 'admin@example.com',
                'password' => 'secret-password',
            ])
            ->assertRedirect('/admin/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest('admin');
        $this->assertStringContainsString(
            'Too many',
            (string) session('errors')->first('email'),
        );
    }

    public function test_successful_login_resets_the_rate_limit_counter(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('secret-password'),
            'is_active' => true,
        ]);

        for ($i = 0; $i < 3; $i++) {
            $this->post('/admin/login', [
                'email' => 'admin@example.com',
                'password' => 'wrong-password',
            ]);
        }

        $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'secret-password',
        ])->assertRedirect('/admin');

        $this->assertAuthenticatedAs($user, 'admin');

        $this->post('/admin/logout')
            ->assertRedirect('/admin/login');

        $this->assertGuest('admin');

        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/login', [
                'email' => 'admin@example.com',
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('email');

            $this->assertStringContainsString(
                'These credentials do not match our records.',
                (string) session('errors')->first('email'),
            );
        }

        $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertStringContainsString(
            'Too many',
            (string) session('errors')->first('email'),
        );
    }

    public function test_forgot_password_requests_are_rate_limited(): void
    {
        Notification::fake();

        User::factory()->create([
            'email' => 'admin@example.com',
            'is_active' => true,
        ]);

        for ($i = 0; $i < 5; $i++) {
            // Clear the password broker's own per-user throttle so each
            // request reaches the controller rate limiter.
            DB::table('password_reset_tokens')->delete();

            $this->from('/admin/forgot-password')
                ->post('/admin/forgot-password', [
                    'email' => 'admin@example.com',
                ])
                ->assertRedirect('/admin/forgot-password')
                ->assertSessionHas('status');
        }

        $this->from('/admin/forgot-password')
            ->post('/admin/forgot-password', [
                'email' => 'admin@example.com',
            ])
            ->assertRedirect('/admin/forgot-password')
            ->assertSessionHasErrors('email');

        $this->assertStringContainsString(
            'Too many',
            (string) session('errors')->first('email'),
        );
    }

    public function test_password_reset_attempts_are_rate_limited(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'is_active' => true,
        ]);

        $payload = [
            'token' => 'bad-token',
            'email' => 'admin@example.com',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ];

        for ($i = 0; $i < 10; $i++) {
            $this->from('/admin/reset-password/bad-token?email=admin@example.com')
                ->post('/admin/reset-password', $payload)
                ->assertRedirect('/admin/reset-password/bad-token?email=admin@example.com')
                ->assertSessionHasErrors('email');
        }

        $this->from('/admin/reset-password/bad-token?email=admin@example.com')
            ->post('/admin/reset-password', $payload)
            ->assertRedirect('/admin/reset-password/bad-token?email=admin@example.com')
            ->assertSessionHasErrors('email');

        $this->assertStringContainsString(
            'Too many',
            (string) session('errors')->first('email'),
        );

        $this->assertTrue(Hash::check('password', User::query()->where('email', 'admin@example.com')->firstOrFail()->password));
    }
}
