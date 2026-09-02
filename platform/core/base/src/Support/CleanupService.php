<?php

namespace Sitewyn\Core\Base\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class CleanupService
{
    /**
     * Tables checked by default because they are usually required to keep the
     * admin account, ACL, settings, sessions, and migration state intact.
     */
    private const DEFAULT_IGNORED_TABLES = [
        'migrations',
        'users',
        'roles',
        'role_user',
        'role_users',
        'permissions',
        'permission_role',
        'role_permission',
        'settings',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'failed_jobs',
    ];

    public function enabled(): bool
    {
        return filter_var(env('CMS_ENABLED_CLEANUP_DATABASE', false), FILTER_VALIDATE_BOOL);
    }

    /**
     * @return array<int, array{name: string, records: int, ignored: bool}>
     */
    public function tables(): array
    {
        return collect(Schema::getTables())
            ->map(fn (array $table): string => (string) ($table['name'] ?? $table['table'] ?? $table[0] ?? ''))
            ->filter()
            ->sort()
            ->values()
            ->map(fn (string $table): array => [
                'name' => $table,
                'records' => $this->records($table),
                'ignored' => in_array($table, self::DEFAULT_IGNORED_TABLES, true),
            ])
            ->all();
    }

    /**
     * @param  array<int, string>  $ignoredTables
     * @return array{cleaned: int, skipped: int}
     */
    public function cleanup(array $ignoredTables): array
    {
        $availableTables = collect($this->tables())->pluck('name')->all();
        $ignoredTables = array_values(array_intersect($ignoredTables, $availableTables));
        $cleanableTables = array_values(array_diff($availableTables, $ignoredTables, ['migrations']));

        if ($cleanableTables === []) {
            return ['cleaned' => 0, 'skipped' => count($ignoredTables)];
        }

        $connection = DB::connection();
        $driver = $connection->getDriverName();

        $this->withoutForeignKeyChecks(function () use ($cleanableTables, $driver): void {
            foreach ($cleanableTables as $table) {
                if ($driver === 'mysql') {
                    DB::statement('TRUNCATE TABLE `'.str_replace('`', '``', $table).'`');

                    continue;
                }

                DB::table($table)->delete();

                if ($driver === 'sqlite') {
                    DB::statement('DELETE FROM sqlite_sequence WHERE name = ?', [$table]);
                }
            }
        });

        return ['cleaned' => count($cleanableTables), 'skipped' => count($ignoredTables)];
    }

    private function records(string $table): int
    {
        try {
            return (int) DB::table($table)->count();
        } catch (Throwable) {
            return 0;
        }
    }

    private function withoutForeignKeyChecks(callable $callback): void
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        }

        try {
            $callback();
        } finally {
            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            } elseif ($driver === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = ON');
            }
        }
    }
}
