<?php

namespace Sitewyn\Core\Base\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Sitewyn\Core\Base\Exceptions\PluginDependencyException;
use Sitewyn\Core\Base\Exceptions\PluginMigrationFailedException;
use Sitewyn\Core\Base\Exceptions\PluginNotFoundException;
use Sitewyn\Core\Base\Models\Plugin;

/**
 * Shared plugin lifecycle engine (P4-06/P4-07): both the console commands
 * and the admin UI go through this service so the dependency rules can
 * never drift apart between the two frontends.
 */
class PluginActivator
{
    public function __construct(
        private readonly PluginManager $manager,
    ) {}

    /**
     * Activate a plugin, running its scoped migrations on the very first
     * activation. Re-activating a known plugin is idempotent.
     *
     * @throws PluginNotFoundException when the slug is not discoverable
     * @throws PluginDependencyException when the requires graph forbids activation
     * @throws PluginMigrationFailedException when first-activation migrations fail
     */
    public function activate(string $slug, bool $runMigrations = true): void
    {
        $manifest = $this->manifest($slug);

        $this->assertNotPartOfCircularChain($slug);
        $this->assertRequirementsSatisfied($slug, $manifest);

        $firstActivation = Plugin::query()->where('slug', $slug)->doesntExist();

        if ($firstActivation && $runMigrations && ! $this->runMigrations('migrate', $manifest)) {
            throw new PluginMigrationFailedException(
                "Migrations for [{$slug}] failed. The plugin was not activated."
            );
        }

        Plugin::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $manifest['name'],
                'version' => $manifest['version'],
                'description' => $manifest['description'],
                'is_active' => true,
                'activated_at' => now(),
            ],
        );

        $this->manager->refresh();
    }

    /**
     * Deactivate a plugin while keeping its data. Migrations are only rolled
     * back when explicitly requested (--rollback stays CLI-only; the UI
     * always keeps plugin data).
     *
     * @throws PluginNotFoundException when the slug is not discoverable
     * @throws PluginDependencyException when active plugins still require it
     * @throws PluginMigrationFailedException when the requested rollback fails
     */
    public function deactivate(string $slug, bool $rollback = false): void
    {
        $manifest = $this->manifest($slug);

        $dependents = $this->activeDependents($slug);

        if ($dependents->isNotEmpty()) {
            throw new PluginDependencyException(
                sprintf(
                    'Plugin [%s] cannot be deactivated: required by active plugin(s): %s.',
                    $slug,
                    $dependents->implode(', '),
                ),
                $slug,
                $dependents->all(),
            );
        }

        Plugin::query()->updateOrCreate(
            ['slug' => $slug],
            ['name' => $manifest['name'], 'is_active' => false],
        );

        $this->manager->refresh();

        if ($rollback && ! $this->runMigrations('migrate:rollback', $manifest)) {
            throw new PluginMigrationFailedException(
                "Rolling back migrations for [{$slug}] failed."
            );
        }
    }

    /**
     * @return array<string, mixed>
     *
     * @throws PluginNotFoundException
     */
    private function manifest(string $slug): array
    {
        $manifest = $this->manager->find($slug);

        if ($manifest === null) {
            throw new PluginNotFoundException("Plugin [{$slug}] does not exist.");
        }

        return $manifest;
    }

    /**
     * Every requirement must be available (discoverable on disk) and active.
     * Requirements are never auto-activated (MVP): the error names them so
     * the operator can activate bottom-up. A plugin that is itself part of
     * a circular requires chain can never satisfy that — it is rejected
     * up-front with an explicit message instead of pointing at the first
     * unmet requirement. A requirement that declared a version constraint
     * (P4-09) must additionally match the dependency's manifest version.
     *
     * @param  array<string, mixed>  $manifest
     *
     * @throws PluginDependencyException
     */
    private function assertRequirementsSatisfied(string $slug, array $manifest): void
    {
        $available = $this->manager->availableSlugs();
        $unavailable = [];
        $inactive = [];

        foreach ($manifest['requires'] as $dependency) {
            if (! in_array($dependency, $available, true)) {
                $unavailable[] = $dependency;

                continue;
            }

            if (! $this->manager->isActive($dependency)) {
                $inactive[] = $dependency;
            }
        }

        if ($unavailable !== []) {
            throw new PluginDependencyException(
                sprintf(
                    'Plugin [%s] requires unavailable plugin(s): %s.',
                    $slug,
                    implode(', ', $unavailable),
                ),
                $slug,
                $unavailable,
            );
        }

        if ($inactive !== []) {
            $single = count($inactive) === 1;

            throw new PluginDependencyException(
                sprintf(
                    'Plugin [%s] requires [%s] which %s inactive. Activate %s first.',
                    $slug,
                    implode(', ', $inactive),
                    $single ? 'is' : 'are',
                    $single ? 'it' : 'them',
                ),
                $slug,
                $inactive,
            );
        }

        $this->assertVersionConstraintsSatisfied($slug, $manifest);
    }

    /**
     * Declared version constraints (P4-09) are checked against the
     * dependency's manifest version. Two light formats are supported —
     * exact ("1.2.3") and caret ("^1.2", meaning 1.2.*). Anything else
     * fails closed: the constraint never matches, so a typo or an
     * unsupported operator surfaces as an activation error naming the
     * constraint, never as a silently ignored one.
     *
     * @param  array<string, mixed>  $manifest
     *
     * @throws PluginDependencyException
     */
    private function assertVersionConstraintsSatisfied(string $slug, array $manifest): void
    {
        foreach ($manifest['constraints'] ?? [] as $dependency => $constraint) {
            $installed = (string) ($this->manager->find((string) $dependency)['version'] ?? '');

            if ($installed !== '' && $this->satisfiesConstraint($installed, (string) $constraint)) {
                continue;
            }

            throw new PluginDependencyException(
                sprintf(
                    'Plugin [%s] requires [%s] %s but version [%s] is installed. Supported constraints: exact "1.2.3" or "^1.2".',
                    $slug,
                    $dependency,
                    $constraint,
                    $installed !== '' ? $installed : 'unknown',
                ),
                $slug,
                [(string) $dependency],
            );
        }
    }

    /**
     * Exact match, or a caret prefix: "^1.2" is satisfied by 1.2 itself and
     * by every 1.2.x version. Deliberately no comparison operators, ranges
     * or wildcards — semver-lite, dependency-free.
     */
    private function satisfiesConstraint(string $version, string $constraint): bool
    {
        if (str_starts_with($constraint, '^')) {
            $prefix = substr($constraint, 1);

            return $version === $prefix || str_starts_with($version, $prefix.'.');
        }

        return $version === $constraint;
    }

    /**
     * Reject plugins sitting on a circular requires chain (A requires B,
     * B requires A): neither can ever be activated first. The walk follows
     * requires edges through available plugins only and only fails when the
     * cycle leads back to the target itself — a merely reachable cycle is
     * the dependency's own problem and is reported when it is activated.
     *
     * @throws PluginDependencyException
     */
    private function assertNotPartOfCircularChain(string $slug): void
    {
        $cycle = $this->circularPath($slug);

        if ($cycle !== null) {
            throw new PluginDependencyException(
                sprintf('Circular dependency detected: %s.', implode(' → ', $cycle)),
                $slug,
                $cycle,
            );
        }
    }

    /**
     * Depth-first walk from the target through requires edges. Returns the
     * cycle path ending at the target (e.g. [a, b, a]) or null when the
     * target is not part of a cycle.
     *
     * @return array<int, string>|null
     */
    private function circularPath(string $slug): ?array
    {
        $plugins = $this->manager->all()->keyBy('slug')->all();

        $path = [$slug];
        $visiting = [$slug => true];
        $done = [];
        $cycle = null;

        $visit = function (string $current) use (&$visit, &$path, &$visiting, &$done, &$cycle, $plugins, $slug): bool {
            foreach ($plugins[$current]['requires'] ?? [] as $dependency) {
                // Unavailable dependencies terminate the walk; they are
                // reported separately by the requirement checks.
                if (! isset($plugins[$dependency]) || isset($done[$dependency])) {
                    continue;
                }

                if ($dependency === $slug) {
                    $path[] = $slug;
                    $cycle = $path;

                    return true;
                }

                // A cycle that does not lead back to the target is not the
                // target's own problem — skip it on this walk.
                if (isset($visiting[$dependency])) {
                    continue;
                }

                $visiting[$dependency] = true;
                $path[] = $dependency;

                if ($visit($dependency)) {
                    return true;
                }

                array_pop($path);
                unset($visiting[$dependency]);
                $done[$dependency] = true;
            }

            return false;
        };

        $visit($slug);

        return $cycle;
    }

    /**
     * Active plugins that transitively require the given slug, recursing
     * through requires edges of active plugins only. Sorted by slug so
     * error messages are deterministic.
     *
     * @return Collection<int, string>
     */
    private function activeDependents(string $slug): Collection
    {
        $active = $this->manager->all()
            ->filter(fn (array $plugin): bool => $plugin['isActive'] && $plugin['slug'] !== $slug);

        $dependents = collect();
        $frontier = collect([$slug]);

        while ($frontier->isNotEmpty()) {
            $current = $frontier->shift();

            $direct = $active
                ->reject(fn (array $plugin): bool => $dependents->containsStrict($plugin['slug']))
                ->filter(fn (array $plugin): bool => in_array($current, $plugin['requires'], true))
                ->pluck('slug');

            $dependents = $dependents->merge($direct);
            $frontier = $frontier->merge($direct);
        }

        return $dependents->unique()->sort()->values();
    }

    /**
     * Run (or roll back) the plugin's scoped migrations. A plugin without a
     * migrations directory is a no-op success.
     *
     * @param  array<string, mixed>  $manifest
     */
    private function runMigrations(string $command, array $manifest): bool
    {
        $path = $this->migrationsPath($manifest);

        if ($path === null) {
            return true;
        }

        return Artisan::call($command, ['--path' => $path, '--force' => true]) === 0;
    }

    /**
     * Migrations directory of the plugin, relative to the base path, or
     * null when the plugin has none.
     *
     * @param  array<string, mixed>  $manifest
     */
    private function migrationsPath(array $manifest): ?string
    {
        $absolute = str_replace('\\', '/', $manifest['path']).'/'.trim($manifest['migrations'], '/\\');

        if (! is_dir($absolute)) {
            return null;
        }

        $basePath = str_replace('\\', '/', base_path());

        return str_starts_with($absolute, $basePath.'/')
            ? substr($absolute, strlen($basePath) + 1)
            : $absolute;
    }
}
