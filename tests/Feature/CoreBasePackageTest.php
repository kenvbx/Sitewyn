<?php

namespace Tests\Feature;

use Tests\TestCase;

class CoreBasePackageTest extends TestCase
{
    public function test_core_base_package_is_loaded(): void
    {
        $this->assertSame('Sitewyn Core Base', config('sitewyn-base.name'));
        $this->assertTrue(view()->exists('core/base::placeholder'));
    }

    public function test_core_base_health_route_responds(): void
    {
        $this->get('/_platform/core/base')
            ->assertOk()
            ->assertJson([
                'module' => 'core/base',
                'status' => 'ok',
            ]);
    }
}
