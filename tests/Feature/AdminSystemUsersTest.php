<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Sitewyn\Core\Base\Models\Permission;
use Sitewyn\Core\Base\Models\Role;
use Tests\TestCase;

class AdminSystemUsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_users_index_requires_the_team_permission(): void
    {
        // users.index alone is the outside surface permission — the team
        // surface needs system.users.index.
        $user = $this->userWithPermissions(['users.index']);

        $this->actingAs($user, 'admin')
            ->get('/admin/system/users')
            ->assertForbidden();
    }

    public function test_super_admin_can_view_team_users_index(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $teamRole = Role::factory()->system()->create([
            'name' => 'Admin',
            'slug' => 'admin',
        ]);
        $teamMember = User::factory()->create([
            'name' => 'Team Member',
            'email' => 'team-member@example.com',
        ]);
        $teamMember->roles()->attach($teamRole);

        $this->actingAs($admin, 'admin')
            ->get('/admin/system/users')
            ->assertOk()
            ->assertSee('Team users')
            ->assertSee('Team Member')
            ->assertSee('team-member@example.com');
    }

    public function test_system_users_index_does_not_list_outside_users(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        User::factory()->create([
            'name' => 'Plain Member',
            'email' => 'plain-member@example.com',
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/system/users')
            ->assertOk()
            ->assertDontSee('Plain Member')
            ->assertDontSee('plain-member@example.com');
    }

    public function test_super_admin_can_create_team_user_with_the_admin_role(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $adminRole = Role::factory()->system()->create([
            'name' => 'Admin',
            'slug' => 'admin',
        ]);

        $this->actingAs($admin, 'admin')
            ->post('/admin/system/users', [
                'name' => 'New Teammate',
                'username' => 'new-teammate',
                'email' => 'new-teammate@example.com',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
                'is_active' => '1',
                'roles' => [$adminRole->id],
            ])
            ->assertRedirect('/admin/system/users')
            ->assertSessionHasNoErrors();

        $user = User::query()->where('email', 'new-teammate@example.com')->firstOrFail();

        $this->assertTrue($user->is_active);
        $this->assertFalse($user->is_super_admin);
        $this->assertTrue($user->isTeamMember());
        $this->assertSame([$adminRole->id], $user->roles()->pluck('roles.id')->all());

        // The new team member shows up on the team list only.
        $this->actingAs($admin, 'admin')
            ->get('/admin/system/users')
            ->assertOk()
            ->assertSee('New Teammate');

        $this->actingAs($admin, 'admin')
            ->get('/admin/users')
            ->assertOk()
            ->assertDontSee('New Teammate');
    }

    public function test_edit_team_user_page_renders_the_tabbed_account_form(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $member = User::factory()->create([
            'name' => 'Tabbed Member',
            'email' => 'tabbed-member@example.com',
        ]);

        $this->actingAs($admin, 'admin')
            ->get("/admin/system/users/{$member->id}/edit")
            ->assertOk()
            ->assertSee('User profile')
            ->assertSee('Change password')
            ->assertSee('Preferences')
            ->assertSee('New password')
            ->assertSee('Confirm password')
            // The main card keeps one shared form: the profile name field, the
            // password inputs and the client-side theme radios all submit.
            ->assertSee('name="name"', false)
            ->assertSee('id="password" name="password"', false)
            ->assertSee('name="admin_theme"', false)
            ->assertSee('More languages coming soon.')
            // Password stays optional on edit.
            ->assertDontSee('required minlength="8"', false)
            // The admin cards outside the tabs are untouched.
            ->assertSee('Account status')
            ->assertSee('Roles')
            ->assertSee('Update');
    }

    public function test_create_team_user_page_renders_the_tabbed_account_form(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/system/users/create')
            ->assertOk()
            ->assertSee('Change password')
            ->assertSee('Preferences')
            ->assertSee('New password')
            ->assertSee('Confirm password')
            // Password is required on create.
            ->assertSee('required minlength="8"', false)
            ->assertSee('Save user');
    }

    public function test_roles_card_renders_a_single_select_with_the_current_role_preselected(): void
    {
        // The switch list became a single select: one role per team member,
        // still submitted as the same roles[] array the backend rules and
        // the escalation guards validate.
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $adminRole = Role::factory()->system()->create([
            'name' => 'Admin',
            'slug' => 'admin',
        ]);
        $editorRole = Role::factory()->create([
            'name' => 'Editor',
            'slug' => 'editor',
        ]);
        $member = User::factory()->create([
            'name' => 'Selected Member',
            'email' => 'selected-member@example.com',
        ]);
        $member->roles()->attach($adminRole);

        $this->actingAs($admin, 'admin')
            ->get("/admin/system/users/{$member->id}/edit")
            ->assertOk()
            ->assertSee('<select class="form-select" name="roles[]" id="roles" aria-label="Role">', false)
            ->assertSee('<option value="">No role</option>', false)
            ->assertSee('<option value="'.$adminRole->id.'" selected>Admin</option>', false)
            // Unselected options keep the space before the (empty) @selected
            // directive, so assert presence and absence separately.
            ->assertSee('<option value="'.$editorRole->id.'"', false)
            ->assertDontSee('<option value="'.$editorRole->id.'" selected', false);
    }

    public function test_saving_a_team_user_without_roles_clears_their_roles(): void
    {
        // "No role" in the select submits no roles field at all (the view
        // disables the control on submit — a native roles[]='' would trip
        // the roles.* integer rule). That payload must detach every role,
        // the same as unchecking all the old switches did.
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $adminRole = Role::factory()->system()->create([
            'name' => 'Admin',
            'slug' => 'admin',
        ]);
        $member = User::factory()->create([
            'name' => 'Roleless Member',
            'email' => 'roleless-member@example.com',
        ]);
        $member->roles()->attach($adminRole);

        $this->actingAs($admin, 'admin')
            ->put("/admin/system/users/{$member->id}", [
                'name' => 'Roleless Member',
                'email' => 'roleless-member@example.com',
            ])
            ->assertRedirect("/admin/system/users/{$member->id}/edit")
            ->assertSessionHasNoErrors();

        $this->assertSame([], $member->refresh()->roles()->pluck('roles.id')->all());
    }

    public function test_edit_team_user_page_renders_the_avatar_tab(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $member = User::factory()->create([
            'name' => 'Avatar Member',
            'email' => 'avatar-member@example.com',
        ]);

        $this->actingAs($admin, 'admin')
            ->get("/admin/system/users/{$member->id}/edit")
            ->assertOk()
            // The avatar tab sits between User profile and Change password.
            ->assertSee('Avatar')
            ->assertSee('Change avatar')
            // Without a stored avatar the Tabler avatar span shows the
            // initials fallback, the remove button stays hidden and the
            // hidden file input joins the main form.
            ->assertSee('name="avatar"', false)
            ->assertSee('avatar-xl" data-avatar-preview', false)
            ->assertSee('>AM</span>', false)
            ->assertSee('hidden >Delete avatar', false);
    }

    public function test_avatar_preview_block_stays_wired_for_users_without_an_avatar(): void
    {
        // Regression: the preview used to hinge on an <img data-avatar-image>
        // tag that only rendered for users WITH a stored avatar, so picking a
        // file did nothing and no remove button appeared for everybody else.
        // The Tabler-style span keeps every hook in the markup either way.
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $member = User::factory()->create([
            'name' => 'Bare Member',
            'email' => 'bare-member@example.com',
        ]);

        $this->assertNull($member->avatar);

        $this->actingAs($admin, 'admin')
            ->get("/admin/system/users/{$member->id}/edit")
            ->assertOk()
            ->assertSee('avatar-xl" data-avatar-preview', false)
            ->assertSee('>BM</span>', false)
            ->assertSee('data-avatar-choose', false)
            ->assertSee('hidden >Delete avatar', false)
            ->assertSee('name="avatar"', false)
            ->assertSee('data-avatar-remove-flag', false);
    }

    public function test_edit_team_user_page_renders_the_image_preview_for_a_stored_avatar(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $member = User::factory()->create([
            'name' => 'Pictured Member',
            'email' => 'pictured-member@example.com',
            'avatar' => 'avatars/seeded.png',
        ]);

        $this->actingAs($admin, 'admin')
            ->get("/admin/system/users/{$member->id}/edit")
            ->assertOk()
            // With a stored avatar the Tabler span carries the picture as a
            // background-image and the remove affordance shows.
            ->assertSee('background-image: url(\''.$member->avatar_url.'\')', false)
            ->assertSee('Delete avatar', false)
            ->assertDontSee('hidden >Delete avatar', false);
    }

    public function test_edit_team_user_page_asks_for_current_password_only_on_self_edit(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $member = User::factory()->create([
            'name' => 'Other Member',
            'email' => 'other-member@example.com',
        ]);

        // Self-edit: the current password field sits above the new password.
        $this->actingAs($admin, 'admin')
            ->get("/admin/system/users/{$admin->id}/edit")
            ->assertOk()
            ->assertSee('Current Password', false)
            ->assertSee('name="current_password"', false);

        // Editing somebody else: the field is never rendered.
        $this->actingAs($admin, 'admin')
            ->get("/admin/system/users/{$member->id}/edit")
            ->assertOk()
            ->assertDontSee('name="current_password"', false);
    }

    public function test_super_admin_can_upload_an_avatar_for_a_team_user(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $member = User::factory()->create([
            'name' => 'Uploaded Member',
            'email' => 'uploaded-member@example.com',
        ]);

        $this->actingAs($admin, 'admin')
            ->put("/admin/system/users/{$member->id}", [
                'name' => 'Uploaded Member',
                'email' => 'uploaded-member@example.com',
                'avatar' => UploadedFile::fake()->image('portrait.png'),
            ])
            ->assertRedirect("/admin/system/users/{$member->id}/edit")
            ->assertSessionHasNoErrors();

        $member->refresh();

        // The upload lands on the public disk under avatars/ with its uuid
        // name, and the header can resolve a public URL from it.
        $this->assertNotNull($member->avatar);
        $this->assertStringStartsWith('avatars/', $member->avatar);
        $this->assertStringEndsWith('.png', $member->avatar);
        Storage::disk('public')->assertExists($member->avatar);

        $this->actingAs($admin, 'admin')
            ->get("/admin/system/users/{$member->id}/edit")
            ->assertOk()
            ->assertSee('background-image: url(\''.$member->avatar_url.'\')', false);
    }

    public function test_avatar_upload_replaces_the_previous_file(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $member = User::factory()->create([
            'name' => 'Replaced Member',
            'email' => 'replaced-member@example.com',
        ]);

        $this->actingAs($admin, 'admin')
            ->put("/admin/system/users/{$member->id}", [
                'name' => 'Replaced Member',
                'email' => 'replaced-member@example.com',
                'avatar' => UploadedFile::fake()->image('first.jpg'),
            ])
            ->assertSessionHasNoErrors();

        $oldAvatar = $member->refresh()->avatar;

        $this->actingAs($admin, 'admin')
            ->put("/admin/system/users/{$member->id}", [
                'name' => 'Replaced Member',
                'email' => 'replaced-member@example.com',
                'avatar' => UploadedFile::fake()->image('second.jpg'),
            ])
            ->assertSessionHasNoErrors();

        $member->refresh();

        $this->assertNotSame($oldAvatar, $member->avatar);
        Storage::disk('public')->assertExists($member->avatar);
        Storage::disk('public')->assertMissing($oldAvatar);
    }

    public function test_avatar_can_be_removed_from_a_team_user(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $member = User::factory()->create([
            'name' => 'Removable Member',
            'email' => 'removable-member@example.com',
            'avatar' => 'avatars/seeded.png',
        ]);
        Storage::disk('public')->put('avatars/seeded.png', 'fake-image');

        $this->actingAs($admin, 'admin')
            ->put("/admin/system/users/{$member->id}", [
                'name' => 'Removable Member',
                'email' => 'removable-member@example.com',
                'avatar_remove' => '1',
            ])
            ->assertRedirect("/admin/system/users/{$member->id}/edit")
            ->assertSessionHasNoErrors();

        $member->refresh();

        $this->assertNull($member->avatar);
        Storage::disk('public')->assertMissing('avatars/seeded.png');

        // Back on the form the initials fallback shows again.
        $this->actingAs($admin, 'admin')
            ->get("/admin/system/users/{$member->id}/edit")
            ->assertOk()
            ->assertSee('avatar-xl" data-avatar-preview', false)
            ->assertSee('>RM</span>', false);
    }

    public function test_avatar_upload_validates_type_and_size(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $member = User::factory()->create([
            'name' => 'Strict Member',
            'email' => 'strict-member@example.com',
        ]);

        // Not an image.
        $this->actingAs($admin, 'admin')
            ->put("/admin/system/users/{$member->id}", [
                'name' => 'Strict Member',
                'email' => 'strict-member@example.com',
                'avatar' => UploadedFile::fake()->create('document.pdf', 10, 'application/pdf'),
            ])
            ->assertSessionHasErrors('avatar');

        // Larger than 2 MB.
        $this->actingAs($admin, 'admin')
            ->put("/admin/system/users/{$member->id}", [
                'name' => 'Strict Member',
                'email' => 'strict-member@example.com',
                'avatar' => UploadedFile::fake()->image('huge.png')->size(3000),
            ])
            ->assertSessionHasErrors('avatar');

        $this->assertNull($member->refresh()->avatar);
    }

    public function test_team_users_index_shows_the_stored_avatar_in_the_name_column(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $teamRole = Role::factory()->system()->create([
            'name' => 'Admin',
            'slug' => 'admin',
        ]);
        $member = User::factory()->create([
            'name' => 'Pictured Teammate',
            'email' => 'pictured-teammate@example.com',
            'avatar' => 'avatars/seeded.png',
        ]);
        $member->roles()->attach($teamRole);

        $this->actingAs($admin, 'admin')
            ->get('/admin/system/users')
            ->assertOk()
            // The name column paints the stored avatar onto the Tabler span.
            ->assertSee('avatar avatar-sm me-2" data-user-avatar', false)
            ->assertSee('background-image: url(\''.$member->avatar_url.'\')', false)
            ->assertDontSee('>PT</span>', false);
    }

    public function test_team_users_index_falls_back_to_initials_without_a_stored_avatar(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $teamRole = Role::factory()->system()->create([
            'name' => 'Admin',
            'slug' => 'admin',
        ]);
        $member = User::factory()->create([
            'name' => 'Bare Teammate',
            'email' => 'bare-teammate@example.com',
        ]);
        $member->roles()->attach($teamRole);

        $this->actingAs($admin, 'admin')
            ->get('/admin/system/users')
            ->assertOk()
            // Without a stored avatar the Tabler span shows the initials.
            ->assertSee('avatar avatar-sm me-2" data-user-avatar', false)
            ->assertSee('>BT</span>', false);
    }

    public function test_self_edit_password_change_requires_the_correct_current_password(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
            'password' => 'secret-password',
        ]);

        // Wrong current password: rejected, the old password still works.
        $this->actingAs($admin, 'admin')
            ->put("/admin/system/users/{$admin->id}", [
                'name' => $admin->name,
                'email' => $admin->email,
                'password' => 'new-secret-password',
                'password_confirmation' => 'new-secret-password',
                'current_password' => 'wrong-password',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('secret-password', $admin->refresh()->password));

        // Correct current password: the change goes through.
        $this->actingAs($admin, 'admin')
            ->put("/admin/system/users/{$admin->id}", [
                'name' => $admin->name,
                'email' => $admin->email,
                'password' => 'new-secret-password',
                'password_confirmation' => 'new-secret-password',
                'current_password' => 'secret-password',
            ])
            ->assertRedirect("/admin/system/users/{$admin->id}/edit")
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('new-secret-password', $admin->refresh()->password));
    }

    public function test_self_edit_password_change_without_current_password_is_rejected(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
            'password' => 'secret-password',
        ]);

        $this->actingAs($admin, 'admin')
            ->put("/admin/system/users/{$admin->id}", [
                'name' => $admin->name,
                'email' => $admin->email,
                'password' => 'new-secret-password',
                'password_confirmation' => 'new-secret-password',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('secret-password', $admin->refresh()->password));
    }

    public function test_non_self_edit_changes_password_without_current_password(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $member = User::factory()->create([
            'name' => 'Reset Member',
            'email' => 'reset-member@example.com',
            'password' => 'old-secret-password',
        ]);

        // An admin resetting somebody else's password never needs (or is
        // even asked for) the target's current password — a stray value is
        // ignored as well.
        $this->actingAs($admin, 'admin')
            ->put("/admin/system/users/{$member->id}", [
                'name' => 'Reset Member',
                'email' => 'reset-member@example.com',
                'password' => 'new-secret-password',
                'password_confirmation' => 'new-secret-password',
                'current_password' => 'whatever',
            ])
            ->assertRedirect("/admin/system/users/{$member->id}/edit")
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('new-secret-password', $member->refresh()->password));
    }

    public function test_header_shows_the_avatar_image_when_the_user_has_one(): void
    {
        $admin = User::factory()->create([
            'name' => 'Header Admin',
            'is_super_admin' => true,
            'is_active' => true,
            'avatar' => 'avatars/header.png',
        ]);

        $this->actingAs($admin, 'admin')
            ->get("/admin/system/users/{$admin->id}/edit")
            ->assertOk()
            // The header dropdown swaps the initials circle for the image.
            ->assertSee('<span class="avatar avatar-sm"><img src="'.e($admin->avatar_url).'"', false)
            ->assertDontSee('<span class="avatar avatar-sm">HA</span>', false);
    }

    public function test_super_admin_cannot_delete_own_account(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->delete("/admin/system/users/{$admin->id}")
            ->assertRedirect('/admin/system/users')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
        ]);
    }

    public function test_super_admin_can_delete_another_team_user(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $adminRole = Role::factory()->system()->create([
            'name' => 'Admin',
            'slug' => 'admin',
        ]);
        $teamMember = User::factory()->create();
        $teamMember->roles()->attach($adminRole);

        $this->actingAs($admin, 'admin')
            ->delete("/admin/system/users/{$teamMember->id}")
            ->assertRedirect('/admin/system/users')
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('users', [
            'id' => $teamMember->id,
        ]);
        $this->assertDatabaseMissing('role_user', [
            'user_id' => $teamMember->id,
        ]);
    }

    public function test_team_users_index_filters_by_status(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $adminRole = Role::factory()->system()->create([
            'name' => 'Admin',
            'slug' => 'admin',
        ]);
        $activeMember = User::factory()->create([
            'name' => 'Active Teammate',
            'email' => 'active-teammate@example.com',
            'is_active' => true,
        ]);
        $activeMember->roles()->attach($adminRole);
        $lockedMember = User::factory()->create([
            'name' => 'Locked Teammate',
            'email' => 'locked-teammate@example.com',
            'is_active' => false,
        ]);
        $lockedMember->roles()->attach($adminRole);

        $this->actingAs($admin, 'admin')
            ->get('/admin/system/users?is_active=1')
            ->assertOk()
            ->assertSee('Active Teammate')
            ->assertDontSee('Locked Teammate');

        $this->actingAs($admin, 'admin')
            ->get('/admin/system/users?is_active=0')
            ->assertOk()
            ->assertSee('Locked Teammate')
            ->assertDontSee('Active Teammate');

        // Unknown values count as no filter at all.
        $this->actingAs($admin, 'admin')
            ->get('/admin/system/users?is_active=maybe')
            ->assertOk()
            ->assertSee('Active Teammate')
            ->assertSee('Locked Teammate');
    }

    public function test_team_users_index_filters_by_role(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $adminRole = Role::factory()->system()->create([
            'name' => 'Admin',
            'slug' => 'admin',
        ]);
        $editorRole = Role::factory()->create([
            'name' => 'Editor',
            'slug' => 'editor',
        ]);
        $editorTeammate = User::factory()->create([
            'name' => 'Editor Teammate',
            'email' => 'editor-teammate@example.com',
        ]);
        $editorTeammate->roles()->attach([$adminRole->id, $editorRole->id]);
        $plainTeammate = User::factory()->create([
            'name' => 'Plain Teammate',
            'email' => 'plain-teammate@example.com',
        ]);
        $plainTeammate->roles()->attach($adminRole);

        // Only teammates holding the Editor role match its id.
        $this->actingAs($admin, 'admin')
            ->get('/admin/system/users?role='.$editorRole->id)
            ->assertOk()
            ->assertSee('Editor Teammate')
            ->assertDontSee('Plain Teammate');

        // The sentinel value filters super admins only.
        $this->actingAs($admin, 'admin')
            ->get('/admin/system/users?role=super')
            ->assertOk()
            ->assertSee($admin->email)
            ->assertDontSee('Editor Teammate')
            ->assertDontSee('Plain Teammate');
    }

    public function test_team_users_index_filters_by_created_date_range(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $adminRole = Role::factory()->system()->create([
            'name' => 'Admin',
            'slug' => 'admin',
        ]);
        $this->createTeamMemberCreatedOn('2026-06-15 09:00:00', $adminRole, [
            'name' => 'June teammate',
            'email' => 'june-teammate@example.com',
        ]);
        $this->createTeamMemberCreatedOn('2026-01-05 09:00:00', $adminRole, [
            'name' => 'January teammate',
            'email' => 'january-teammate@example.com',
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/system/users?created_from=2026-06-01&created_to=2026-06-30')
            ->assertOk()
            ->assertSee('June teammate')
            ->assertDontSee('January teammate');

        // Only one bound set: the other side stays open.
        $this->actingAs($admin, 'admin')
            ->get('/admin/system/users?created_from=2026-02-01')
            ->assertOk()
            ->assertSee('June teammate')
            ->assertDontSee('January teammate');
    }

    public function test_team_users_index_ignores_invalid_created_date_filters(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $adminRole = Role::factory()->system()->create([
            'name' => 'Admin',
            'slug' => 'admin',
        ]);
        $this->createTeamMemberCreatedOn('2026-06-15 09:00:00', $adminRole, [
            'name' => 'June teammate',
            'email' => 'june-teammate@example.com',
        ]);
        $this->createTeamMemberCreatedOn('2026-01-05 09:00:00', $adminRole, [
            'name' => 'January teammate',
            'email' => 'january-teammate@example.com',
        ]);

        // Non-dates and impossible dates count as no filter at all.
        $this->actingAs($admin, 'admin')
            ->get('/admin/system/users?created_from=not-a-date&created_to=2026-13-99')
            ->assertOk()
            ->assertSee('June teammate')
            ->assertSee('January teammate');
    }

    public function test_team_users_index_shows_active_filter_count_on_filters_button(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);
        $adminRole = Role::factory()->system()->create([
            'name' => 'Admin',
            'slug' => 'admin',
        ]);
        $member = User::factory()->create([
            'name' => 'Role Teammate',
            'email' => 'role-teammate@example.com',
        ]);
        $member->roles()->attach($adminRole);

        $this->actingAs($admin, 'admin')
            ->get('/admin/system/users?is_active=0&created_from=2026-01-01')
            ->assertOk()
            ->assertSee('<span class="badge bg-blue-lt">2</span>', false);

        $this->actingAs($admin, 'admin')
            ->get('/admin/system/users')
            ->assertOk()
            ->assertDontSee('<span class="badge bg-blue-lt">', false);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createTeamMemberCreatedOn(string $createdAt, Role $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->roles()->attach($role);
        $user->forceFill(['created_at' => $createdAt])->save();

        return $user->refresh();
    }

    /**
     * @param  array<int, string>  $permissionKeys
     */
    private function userWithPermissions(array $permissionKeys): User
    {
        $user = User::factory()->create([
            'is_super_admin' => false,
            'is_active' => true,
        ]);
        $role = Role::factory()->create();

        foreach ($permissionKeys as $key) {
            $role->permissions()->attach(
                Permission::query()->firstOrCreate(
                    ['key' => $key],
                    ['name' => $key, 'module' => 'core/base', 'group' => 'system users', 'description' => null],
                ),
            );
        }

        $user->roles()->attach($role);

        return $user;
    }
}
