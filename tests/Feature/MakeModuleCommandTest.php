<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MakeModuleCommandTest extends TestCase
{
    private string $modulePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modulePath = base_path('platform/plugins/example-module');
        File::deleteDirectory($this->modulePath);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->modulePath);

        parent::tearDown();
    }

    public function test_it_scaffolds_a_platform_module(): void
    {
        $this->artisan('module:make', [
            'type' => 'plugin',
            'name' => 'example-module',
        ])->assertSuccessful();

        $this->assertFileExists($this->modulePath . '/composer.json');
        $this->assertFileExists($this->modulePath . '/package.json');
        $this->assertFileExists($this->modulePath . '/src/Providers/ExampleModuleServiceProvider.php');
        $this->assertFileExists($this->modulePath . '/routes/web.php');
        $this->assertStringContainsString('sitewyn/plugin-example-module', File::get($this->modulePath . '/composer.json'));
        $this->assertStringContainsString('Sitewyn\\\\Plugins\\\\ExampleModule\\\\', File::get($this->modulePath . '/composer.json'));
    }

    public function test_it_refuses_to_overwrite_existing_modules_without_force(): void
    {
        File::ensureDirectoryExists($this->modulePath);

        $this->artisan('module:make', [
            'type' => 'plugin',
            'name' => 'example-module',
        ])->assertFailed();
    }
}
