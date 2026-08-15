<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AdminPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_forgot_password_page_is_visible(): void
    {
        $this->get('/admin/forgot-password')
            ->assertOk()
            ->assertSee('Forgot password')
            ->assertSee('Send me new password');
    }

    public function test_admin_can_request_password_reset_link(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'is_active' => true,
        ]);

        $this->post('/admin/forgot-password', [
            'email' => 'admin@example.com',
        ])->assertRedirect()
            ->assertSessionHas('status', __(Password::RESET_LINK_SENT));

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
            $mail = $notification->toMail($user);
            $actionUrl = $mail->actionUrl ?? '';

            return str_contains($actionUrl, '/admin/reset-password/')
                && str_contains($actionUrl, 'email=admin%40example.com');
        });
    }

    public function test_admin_reset_password_page_is_visible(): void
    {
        $this->get('/admin/reset-password/test-token?email=admin@example.com')
            ->assertOk()
            ->assertSee('Reset password')
            ->assertSee('admin@example.com');
    }

    public function test_admin_can_reset_password(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('old-password'),
            'is_active' => true,
        ]);

        $token = Password::broker()->createToken($user);

        $this->post('/admin/reset-password', [
            'token' => $token,
            'email' => 'admin@example.com',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect('/admin/login')
            ->assertSessionHas('status', __(Password::PASSWORD_RESET));

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    public function test_admin_reset_password_rejects_invalid_token(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'is_active' => true,
        ]);

        $this->from('/admin/reset-password/bad-token?email=admin@example.com')
            ->post('/admin/reset-password', [
                'token' => 'bad-token',
                'email' => 'admin@example.com',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertRedirect('/admin/reset-password/bad-token?email=admin@example.com')
            ->assertSessionHasErrors('email');
    }
}
