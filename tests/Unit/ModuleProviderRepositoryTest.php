<?php

namespace Tests\Unit;

use Illuminate\Foundation\Application;
use PHPUnit\Framework\TestCase;
use Sitewyn\Core\Base\Support\ModuleProviderRepository;

class ModuleProviderRepositoryTest extends TestCase
{
    public function test_it_discovers_and_loads_module_providers_from_platform_composer_files(): void
    {
        $basePath = sys_get_temp_dir() . '/sitewyn-module-provider-test-' . bin2hex(random_bytes(4));
        $modulePath = $basePath . '/platform/plugins/sample';

        mkdir($modulePath . '/src/Providers', 0777, true);

        file_put_contents($modulePath . '/composer.json', json_encode([
            'name' => 'sitewyn/plugin-sample',
            'autoload' => [
                'psr-4' => [
                    'Sitewyn\\Plugins\\Sample\\' => 'src/',
                ],
            ],
            'extra' => [
                'laravel' => [
                    'providers' => [
                        'Sitewyn\\Plugins\\Sample\\Providers\\SampleServiceProvider',
                    ],
                ],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        file_put_contents($modulePath . '/src/Providers/SampleServiceProvider.php', <<<'PHP'
<?php

namespace Sitewyn\Plugins\Sample\Providers;

use Illuminate\Support\ServiceProvider;

class SampleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->instance('sitewyn.sample.loaded', true);
    }
}
PHP);

        $repository = new ModuleProviderRepository($basePath);
        $providers = $repository->providers(['platform/plugins']);

        $this->assertSame([
            'Sitewyn\\Plugins\\Sample\\Providers\\SampleServiceProvider',
        ], $providers);

        $app = new Application($basePath);
        $app->register($providers[0]);

        $this->assertTrue($app->make('sitewyn.sample.loaded'));
    }
}
