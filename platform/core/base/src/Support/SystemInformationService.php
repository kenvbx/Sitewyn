<?php

namespace Sitewyn\Core\Base\Support;

use Composer\InstalledVersions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

class SystemInformationService
{
    /**
     * @return array<int, array{name: string, version: string, dependencies: array<string, string>}>
     */
    public function packages(): array
    {
        $lockPath = base_path('composer.lock');

        if (! File::exists($lockPath)) {
            return [];
        }

        $lock = json_decode((string) File::get($lockPath), true);
        $packages = array_merge($lock['packages'] ?? [], $lock['packages-dev'] ?? []);

        return collect($packages)
            ->map(fn (array $package): array => [
                'name' => (string) ($package['name'] ?? ''),
                'version' => (string) ($package['version'] ?? ''),
                'dependencies' => collect($package['require'] ?? [])
                    ->mapWithKeys(fn (string $version, string $name): array => [$name => $version])
                    ->all(),
            ])
            ->filter(fn (array $package): bool => $package['name'] !== '')
            ->sortBy('name')
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{label: string, value: string, ok?: bool|null, copyable?: bool}>
     */
    public function systemEnvironment(Request $request): array
    {
        return [
            ['label' => 'CMS Version', 'value' => $this->packageVersion('sitewyn/cms')],
            ['label' => 'Core Version', 'value' => $this->packageVersion('sitewyn/core-base')],
            ['label' => 'Framework Version', 'value' => app()->version()],
            ['label' => 'Timezone', 'value' => config('app.timezone')],
            ['label' => 'Server IP', 'value' => $request->server('SERVER_ADDR') ?: gethostbyname(gethostname() ?: 'localhost'), 'copyable' => true],
            ['label' => 'Debug Mode Off', 'value' => config('app.debug') ? 'No' : 'Yes', 'ok' => ! config('app.debug')],
            ['label' => 'Storage Dir Writable', 'value' => File::isWritable(storage_path()) ? 'Yes' : 'No', 'ok' => File::isWritable(storage_path())],
            ['label' => 'Cache Dir Writable', 'value' => File::isWritable(storage_path('framework/cache')) ? 'Yes' : 'No', 'ok' => File::isWritable(storage_path('framework/cache'))],
            ['label' => 'App Size', 'value' => $this->formatBytes($this->directorySize(base_path()))],
        ];
    }

    /**
     * @return array<int, array{label: string, value: string, ok?: bool|null}>
     */
    public function serverEnvironment(Request $request): array
    {
        return [
            ['label' => 'PHP Version', 'value' => PHP_VERSION, 'ok' => version_compare(PHP_VERSION, '8.3.0', '>=')],
            ['label' => 'OPcache Enabled', 'value' => $this->enabled(extension_loaded('Zend OPcache') && filter_var(ini_get('opcache.enable'), FILTER_VALIDATE_BOOL))],
            ['label' => 'Memory limit', 'value' => (string) ini_get('memory_limit')],
            ['label' => 'Max execution time (s)', 'value' => (string) ini_get('max_execution_time')],
            ['label' => 'Server Software', 'value' => (string) $request->server('SERVER_SOFTWARE', PHP_SAPI)],
            ['label' => 'Server OS', 'value' => php_uname()],
            ['label' => 'Database', 'value' => DB::connection()->getDriverName()],
            ['label' => 'SSL Installed', 'value' => $this->enabled($request->isSecure()), 'ok' => $request->isSecure()],
            ['label' => 'Cache Driver', 'value' => (string) config('cache.default')],
            ['label' => 'Session Driver', 'value' => (string) config('session.driver')],
            ['label' => 'Queue Connection', 'value' => (string) config('queue.default')],
            ['label' => 'allow_url_fopen enabled', 'value' => $this->enabled((bool) ini_get('allow_url_fopen')), 'ok' => (bool) ini_get('allow_url_fopen')],
            ['label' => 'OpenSSL Ext', 'value' => $this->enabled(extension_loaded('openssl')), 'ok' => extension_loaded('openssl')],
            ['label' => 'Mbstring Ext', 'value' => $this->enabled(extension_loaded('mbstring')), 'ok' => extension_loaded('mbstring')],
            ['label' => 'PDO Ext', 'value' => $this->enabled(extension_loaded('pdo')), 'ok' => extension_loaded('pdo')],
            ['label' => 'CURL Ext', 'value' => $this->enabled(extension_loaded('curl')), 'ok' => extension_loaded('curl')],
            ['label' => 'Exif Ext', 'value' => $this->enabled(extension_loaded('exif')), 'ok' => extension_loaded('exif')],
            ['label' => 'File info Ext', 'value' => $this->enabled(extension_loaded('fileinfo')), 'ok' => extension_loaded('fileinfo')],
            ['label' => 'Tokenizer Ext', 'value' => $this->enabled(extension_loaded('tokenizer')), 'ok' => extension_loaded('tokenizer')],
            ['label' => 'Imagick/GD Ext', 'value' => $this->enabled(extension_loaded('imagick') || extension_loaded('gd')), 'ok' => extension_loaded('imagick') || extension_loaded('gd')],
            ['label' => 'Zip Ext', 'value' => $this->enabled(extension_loaded('zip')), 'ok' => extension_loaded('zip')],
            ['label' => 'Iconv Ext', 'value' => $this->enabled(extension_loaded('iconv')), 'ok' => extension_loaded('iconv')],
            ['label' => 'JSON Ext', 'value' => $this->enabled(extension_loaded('json')), 'ok' => extension_loaded('json')],
        ];
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    public function databaseInformation(): array
    {
        $connection = DB::connection();

        return [
            ['label' => 'Database Driver', 'value' => $connection->getDriverName()],
            ['label' => 'Database Name', 'value' => (string) $connection->getDatabaseName()],
            ['label' => 'Database Version', 'value' => $this->databaseVersion()],
            ['label' => 'Max Connections', 'value' => $this->maxConnections()],
            ['label' => 'Character Set', 'value' => (string) config('database.connections.'.$connection->getName().'.charset', 'N/A')],
            ['label' => 'Collation', 'value' => (string) config('database.connections.'.$connection->getName().'.collation', 'N/A')],
        ];
    }

    /**
     * @return array<int, array{label: string, value: string, ok?: bool|null}>
     */
    public function phpConfiguration(): array
    {
        return [
            ['label' => 'POST Max Size', 'value' => (string) ini_get('post_max_size')],
            ['label' => 'Upload Max Filesize', 'value' => (string) ini_get('upload_max_filesize')],
            ['label' => 'Max File Uploads', 'value' => (string) ini_get('max_file_uploads')],
            ['label' => 'Max Input Time', 'value' => ini_get('max_input_time').' seconds'],
            ['label' => 'Max Input Vars', 'value' => (string) ini_get('max_input_vars')],
            ['label' => 'Display Errors', 'value' => $this->enabled((bool) ini_get('display_errors')), 'ok' => (bool) ini_get('display_errors')],
            ['label' => 'Error Reporting Level', 'value' => (string) error_reporting()],
            ['label' => 'Date Timezone', 'value' => (string) ini_get('date.timezone')],
        ];
    }

    public function report(Request $request): string
    {
        $sections = [
            'System Environment' => $this->systemEnvironment($request),
            'Server Environment' => $this->serverEnvironment($request),
            'Database Information' => $this->databaseInformation(),
            'PHP Configuration' => $this->phpConfiguration(),
        ];

        return collect($sections)
            ->map(function (array $rows, string $title): string {
                $lines = ['### '.$title, ''];

                foreach ($rows as $row) {
                    $suffix = array_key_exists('ok', $row) ? ($row['ok'] ? ' ✓' : ' ✗') : '';
                    $lines[] = '- '.$row['label'].': '.$row['value'].$suffix;
                }

                return implode("\n", $lines);
            })
            ->implode("\n\n");
    }

    private function packageVersion(string $package): string
    {
        if ($package === 'sitewyn/cms') {
            return '0.1.0';
        }

        try {
            return InstalledVersions::getPrettyVersion($package) ?? 'N/A';
        } catch (Throwable) {
            return 'N/A';
        }
    }

    private function databaseVersion(): string
    {
        try {
            return (string) DB::connection()->getPdo()->getAttribute(\PDO::ATTR_SERVER_VERSION);
        } catch (Throwable) {
            return 'N/A';
        }
    }

    private function maxConnections(): string
    {
        try {
            $driver = DB::connection()->getDriverName();

            if ($driver === 'mysql') {
                return (string) DB::selectOne('SHOW VARIABLES LIKE "max_connections"')?->Value;
            }
        } catch (Throwable) {
            return 'N/A';
        }

        return 'N/A';
    }

    private function enabled(bool $value): string
    {
        return $value ? 'Yes' : 'No';
    }

    private function directorySize(string $path): int
    {
        if (! File::isDirectory($path)) {
            return 0;
        }

        $size = 0;

        foreach (File::allFiles($path) as $file) {
            if (str_contains($file->getPathname(), DIRECTORY_SEPARATOR.'.git'.DIRECTORY_SEPARATOR)) {
                continue;
            }

            $size += $file->getSize();
        }

        return $size;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        }

        return $bytes.' B';
    }
}
