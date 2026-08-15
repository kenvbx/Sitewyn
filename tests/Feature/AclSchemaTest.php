<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AclSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_acl_tables_are_created(): void
    {
        $this->assertTrue(Schema::hasTable('roles'));
        $this->assertTrue(Schema::hasTable('permissions'));
        $this->assertTrue(Schema::hasTable('role_user'));
        $this->assertTrue(Schema::hasTable('permission_role'));
        $this->assertTrue(Schema::hasTable('user_meta'));
    }

    public function test_users_table_has_admin_acl_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('users', [
            'username',
            'is_super_admin',
            'is_active',
            'last_login_at',
        ]));
    }

    public function test_acl_tables_have_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('roles', [
            'id',
            'name',
            'slug',
            'description',
            'is_system',
            'created_at',
            'updated_at',
        ]));

        $this->assertTrue(Schema::hasColumns('permissions', [
            'id',
            'name',
            'key',
            'module',
            'group',
            'description',
            'created_at',
            'updated_at',
        ]));

        $this->assertTrue(Schema::hasColumns('user_meta', [
            'id',
            'user_id',
            'key',
            'value',
            'created_at',
            'updated_at',
        ]));
    }
}
