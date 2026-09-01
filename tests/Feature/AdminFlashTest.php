<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFlashTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_flash_helper_sets_toast_payload_and_legacy_status(): void
    {
        admin_flash()->success('Saved successfully.');

        $this->assertSame([
            'type' => 'success',
            'title' => 'Success',
            'message' => 'Saved successfully.',
        ], session('admin_flash'));
        $this->assertSame('Saved successfully.', session('status'));
    }

    public function test_master_layout_renders_session_flash_as_tabler_toast(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($user, 'admin')
            ->withSession([
                'admin_flash' => [
                    'type' => 'success',
                    'title' => 'Saved',
                    'message' => 'Role updated successfully.',
                ],
            ])
            ->get('/admin/system/roles')
            ->assertOk()
            ->assertSee('toast-container position-fixed bottom-0 end-0 p-3', false)
            ->assertSee('id="admin-flash-toast"', false)
            ->assertSee('Role updated successfully.')
            ->assertSee('Saved');
    }
}
