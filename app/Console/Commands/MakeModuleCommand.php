<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class MakeModuleCommand extends Command
{
    protected $signature = 'module:make
        {type : Module type: core, package, plugin, theme}
        {name : Module folder name}
        {--force : Overwrite existing generated files}';

    protected $description = 'Scaffold a platform module package';

    /**
     * @var array<string, array{root: string, namespace: string, package_prefix: string, frontend_prefix: string}>
     */
    private array $types = [
        'core' => [
            'root' => 'platform/core',
            'namespace' => 'Core',
            'package_prefix' => 'core',
            'frontend_prefix' => 'core',
        ],
        'package' => [
            'root' => 'platform/packages',
            'namespace' => 'Packages',
            'package_prefix' => 'package',
            'frontend_prefix' => 'package',
        ],
        'plugin' => [
            'root' => 'platform/plugins',
            'namespace' => 'Plugins',
            'package_prefix' => 'plugin',
            'frontend_prefix' => 'plugin',
        ],
        'theme' => [
            'root' => 'platform/themes',
            'namespace' => 'Themes',
            'package_prefix' => 'theme',
            'frontend_prefix' => 'theme',
        ],
    ];

    public function handle(): int
    {
        try {
            $type = $this->normalizeType((string) $this->argument('type'));
            $name = Str::kebab((string) $this->argument('name'));
            $config = $this->types[$type];
            $modulePath = base_path($config['root'] . DIRECTORY_SEPARATOR . $name);
            $force = (bool) $this->option('force');

            if (is_dir($modulePath) && ! $force) {
                $this->components->error("Module [{$type}/{$name}] already exists.");

                return self::FAILURE;
            }

            $this->scaffold($modulePath, $type, $name, $config);
        } catch (InvalidArgumentException|RuntimeException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Module [{$type}/{$name}] created successfully.");

        return self::SUCCESS;
    }

    private function normalizeType(string $type): string
    {
        $type = Str::lower($type);

        return match ($type) {
            'core' => 'core',
            'package', 'packages' => 'package',
            'plugin', 'plugins' => 'plugin',
            'theme', 'themes' => 'theme',
            default => throw new InvalidArgumentException('Invalid module type. Use core, package, plugin, or theme.'),
        };
    }

    /**
     * @param  array{root: string, namespace: string, package_prefix: string, frontend_prefix: string}  $config
     */
    private function scaffold(string $modulePath, string $type, string $name, array $config): void
    {
        $studlyName = Str::studly($name);
        $providerClass = "{$studlyName}ServiceProvider";
        $namespace = "Sitewyn\\{$config['namespace']}\\{$studlyName}";
        $packageName = "sitewyn/{$config['package_prefix']}-{$name}";
        $frontendPackageName = "@sitewyn/{$config['frontend_prefix']}-{$name}";
        $viewNamespace = $type === 'core' ? "core/{$name}" : "{$type}/{$name}";

        $files = [
            'composer.json' => $this->composerJson($packageName, $namespace, $providerClass),
            'package.json' => $this->packageJson($frontendPackageName),
            'config/' . $name . '.php' => $this->phpConfig($studlyName),
            'routes/web.php' => $this->routes($type, $name),
            'src/Providers/' . $providerClass . '.php' => $this->provider($namespace, $providerClass, $name, $viewNamespace),
            'resources/views/placeholder.blade.php' => '<div data-module="' . e($type . '/' . $name) . '"></div>' . PHP_EOL,
            'resources/css/admin.css' => "/* {$type}/{$name} admin styles */" . PHP_EOL,
            'resources/js/admin.js' => "window.Sitewyn = window.Sitewyn || {};" . PHP_EOL,
            'database/migrations/.gitkeep' => '',
        ];

        foreach ($files as $relativePath => $contents) {
            $this->writeFile($modulePath . DIRECTORY_SEPARATOR . $relativePath, $contents);
        }
    }

    private function composerJson(string $packageName, string $namespace, string $providerClass): string
    {
        return $this->json([
            'name' => $packageName,
            'version' => '0.1.0',
            'type' => 'library',
            'license' => 'MIT',
            'autoload' => [
                'psr-4' => [
                    $namespace . '\\' => 'src/',
                ],
            ],
            'extra' => [
                'laravel' => [
                    'providers' => [
                        $namespace . '\\Providers\\' . $providerClass,
                    ],
                ],
            ],
        ]);
    }

    private function packageJson(string $frontendPackageName): string
    {
        return $this->json([
            'name' => $frontendPackageName,
            'private' => true,
            'type' => 'module',
        ]);
    }

    private function phpConfig(string $studlyName): string
    {
        return <<<PHP
<?php

return [
    'name' => '{$studlyName}',
];
PHP;
    }

    private function routes(string $type, string $name): string
    {
        return <<<PHP
<?php

use Illuminate\Support\Facades\Route;

Route::get('/_platform/{$type}/{$name}', static fn () => response()->json([
    'module' => '{$type}/{$name}',
    'status' => 'ok',
]))->name('platform.{$type}.{$name}.health');
PHP;
    }

    private function provider(string $namespace, string $providerClass, string $name, string $viewNamespace): string
    {
        return <<<PHP
<?php

namespace {$namespace}\\Providers;

use Illuminate\Support\ServiceProvider;

class {$providerClass} extends ServiceProvider
{
    public function register(): void
    {
        \$this->mergeConfigFrom(\$this->modulePath('config/{$name}.php'), '{$name}');
    }

    public function boot(): void
    {
        \$this->loadViewsFrom(\$this->modulePath('resources/views'), '{$viewNamespace}');
        \$this->loadRoutesFrom(\$this->modulePath('routes/web.php'));
        \$this->loadMigrationsFrom(\$this->modulePath('database/migrations'));
    }

    private function modulePath(string \$path = ''): string
    {
        \$basePath = dirname(__DIR__, 2);

        return \$path === '' ? \$basePath : \$basePath . DIRECTORY_SEPARATOR . \$path;
    }
}
PHP;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function json(array $payload): string
    {
        return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    }

    private function writeFile(string $path, string $contents): void
    {
        $directory = dirname($path);

        if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
            throw new RuntimeException("Unable to create directory [{$directory}].");
        }

        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException("Unable to write file [{$path}].");
        }
    }
}
