<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminClientValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_create_form_renders_tabler_client_validation_markup(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/users/create')
            ->assertOk()
            ->assertSee('class="needs-validation"', false)
            ->assertSee('data-admin-validate', false)
            ->assertSee('novalidate', false)
            ->assertSee('name="name"', false)
            ->assertSee('required', false)
            ->assertSee('maxlength="255"', false)
            ->assertSee('type="email"', false)
            ->assertSee('minlength="8"', false)
            ->assertSee('data-admin-confirm="password"', false)
            ->assertSee('Password confirmation does not match.', false);
    }

    public function test_role_create_form_renders_tabler_client_validation_markup(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/system/roles/create')
            ->assertOk()
            ->assertSee('class="needs-validation"', false)
            ->assertSee('data-admin-validate', false)
            ->assertSee('novalidate', false)
            ->assertSee('name="name"', false)
            ->assertSee('required', false)
            ->assertSee('maxlength="255"', false)
            ->assertSee('pattern="[A-Za-z0-9_-]+"', false)
            ->assertSee('invalid-feedback', false);
    }

    public function test_user_form_request_keeps_server_side_validation(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->from('/admin/users/create')
            ->post('/admin/users', [
                'name' => '',
                'email' => 'not-an-email',
                'password' => 'short',
                'password_confirmation' => 'different',
            ])
            ->assertRedirect('/admin/users/create')
            ->assertSessionHasErrors(['name', 'email', 'password']);
    }

    public function test_role_form_request_keeps_server_side_validation(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->from('/admin/system/roles/create')
            ->post('/admin/system/roles', [
                'name' => '',
                'slug' => 'invalid slug',
            ])
            ->assertRedirect('/admin/system/roles/create')
            ->assertSessionHasErrors(['name', 'slug']);
    }
}
