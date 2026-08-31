<?php

namespace Sitewyn\Core\Base\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * Filesystem backups of the site: a JSON data dump of every user table plus a
 * mirror of the public media disk, bundled into one ZIP archive.
 *
 * Backups live on the private `local` disk (never `public`), so a backup never
 * becomes downloadable by visitors. Restoring truncates the tables that exist
 * both in the dump and in the current schema, re-inserts the dumped rows, and
 * replaces the media disk with the archived files — a full snapshot rollback.
 */
class BackupService
{
    private const FILENAME_PATTERN = '/^backup-[A-Za-z0-9_-]+\.zip$/';

    private const BACKUP_DIRECTORY = 'backups';

    private const DATABASE_ENTRY = 'database.json';

    private const FILES_PREFIX = 'files/';

    /**
     * The schema always comes from the current migrations, so the dump stores
     * rows only and never creates or alters tables on restore.
     */
    private const EXCLUDED_TABLES = ['migrations'];

    private const INSERT_CHUNK_SIZE = 100;

    /**
     * @return array<int, array{name: string, sizeBytes: int, createdAt: string}>
     */
    public function list(): array
    {
        $disk = $this->localDisk();

        if (! $disk->exists(self::BACKUP_DIRECTORY)) {
            return [];
        }

        $backups = [];

        foreach ($disk->files(self::BACKUP_DIRECTORY) as $path) {
            $name = basename($path);

            if (! $this->isValidName($name)) {
                continue;
            }

            $backups[] = [
                'name' => $name,
                'sizeBytes' => (int) $disk->size($path),
                'createdAt' => date('Y-m-d H:i:s', $disk->lastModified($path)),
            ];
        }

        // Names embed the creation timestamp, so a string sort is newest first.
        usort($backups, fn (array $a, array $b): int => strcmp($b['name'], $a['name']));

        return $backups;
    }

    /**
     * Create a new backup and return its archive file name.
     *
     * @throws RuntimeException when the archive cannot be written
     */
    public function create(): string
    {
        $name = $this->uniqueName();
        $disk = $this->localDisk();
        $disk->makeDirectory(self::BACKUP_DIRECTORY);
        $path = $disk->path(self::BACKUP_DIRECTORY.'/'.$name);

        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Cannot create backup archive [{$name}].");
        }

        try {
            $zip->addFromString(self::DATABASE_ENTRY, $this->databaseDumpJson());
            $this->addMediaFiles($zip);
            $zip->close();
        } catch (Throwable $exception) {
            $zip->close();

            if (is_file($path)) {
                @unlink($path);
            }

            throw $exception;
        }

        return $name;
    }

    /**
     * Absolute path of a backup archive, or an exception when the name is
     * invalid or the file is missing. The strict pattern is the only guard
     * needed against path traversal on download/restore/delete.
     *
     * @throws InvalidArgumentException
     */
    public function download(string $name): string
    {
        return $this->backupPath($name);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function delete(string $name): void
    {
        $this->backupPath($name);
        $this->localDisk()->delete(self::BACKUP_DIRECTORY.'/'.$name);
    }

    /**
     * Replace the current database rows and media disk with the snapshot stored
     * in the archive. Tables present in the dump but missing from the current
     * schema are skipped (with a warning) instead of being created.
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function restore(string $name): void
    {
        $path = $this->backupPath($name);
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::CHECKCONS) !== true) {
            throw new RuntimeException("Cannot open backup archive [{$name}]. It may be corrupt.");
        }

        try {
            $this->restoreDatabase($zip);
            $this->restoreMediaFiles($zip);
        } finally {
            $zip->close();
        }
    }

    private function databaseDumpJson(): string
    {
        $dump = [];

        foreach ($this->userTableNames() as $table) {
            $dump[$table] = DB::table($table)
                ->get()
                ->map(fn (mixed $row): array => (array) $row)
                ->all();
        }

        return (string) json_encode(
            $dump,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }

    private function addMediaFiles(ZipArchive $zip): void
    {
        $disk = $this->mediaDisk();

        foreach ($disk->allFiles() as $path) {
            $entry = self::FILES_PREFIX.$path;

            // Local adapters store real files: add them zero-copy. Any other
            // driver (cloud disks) is read into memory and added as a string.
            $localPath = null;

            try {
                $localPath = $disk->path($path);
            } catch (RuntimeException) {
                // Driver without path support — fall through to read().
            }

            if ($localPath !== null && is_file($localPath)) {
                if ($zip->addFile($localPath, $entry) === false) {
                    throw new RuntimeException("Cannot add media file [{$path}] to the backup archive.");
                }

                continue;
            }

            $contents = $disk->get($path);

            if ($contents === false || $contents === null) {
                throw new RuntimeException("Cannot read media file [{$path}] for backup.");
            }

            if ($zip->addFromString($entry, (string) $contents) === false) {
                throw new RuntimeException("Cannot add media file [{$path}] to the backup archive.");
            }
        }
    }

    private function restoreDatabase(ZipArchive $zip): void
    {
        $json = $zip->getFromName(self::DATABASE_ENTRY);

        if ($json === false) {
            throw new RuntimeException('Backup does not contain a database dump.');
        }

        $dump = json_decode((string) $json, true);

        if (! is_array($dump)) {
            throw new RuntimeException('Backup contains an invalid database dump.');
        }

        $existing = $this->userTableNames();

        $this->disableForeignKeyChecks();

        try {
            foreach ($dump as $table => $rows) {
                if (! is_string($table) || ! is_array($rows) || ! $this->isValidTableName($table)) {
                    continue;
                }

                // Schema is owned by the current migrations: a table that no
                // longer exists in this install is skipped, never created.
                if (! in_array($table, $existing, true)) {
                    logger()->warning("Backup restore skipped table [{$table}] because it does not exist in the current database.");

                    continue;
                }

                DB::table($table)->truncate();

                foreach (array_chunk($rows, self::INSERT_CHUNK_SIZE) as $chunk) {
                    if ($chunk !== []) {
                        DB::table($table)->insert($chunk);
                    }
                }
            }
        } finally {
            $this->enableForeignKeyChecks();
        }
    }

    private function restoreMediaFiles(ZipArchive $zip): void
    {
        $disk = $this->mediaDisk();

        // Restoring is a full snapshot: the current media disk is emptied
        // first, then repopulated from the archive.
        foreach ($disk->allDirectories() as $directory) {
            $disk->deleteDirectory($directory);
        }

        foreach ($disk->allFiles() as $path) {
            $disk->delete($path);
        }

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entry = (string) $zip->getNameIndex($index);

            if (! str_starts_with($entry, self::FILES_PREFIX)) {
                continue;
            }

            $relative = substr($entry, strlen(self::FILES_PREFIX));

            // Directory entries end with a slash and carry no content.
            if ($relative === '' || str_ends_with($relative, '/')) {
                continue;
            }

            if (! $this->isSafeRelativePath($relative)) {
                logger()->warning("Backup restore skipped unsafe archive entry [{$entry}].");

                continue;
            }

            $stream = $zip->getStream($entry);

            if (! is_resource($stream)) {
                throw new RuntimeException("Cannot extract media file [{$relative}] from the backup archive.");
            }

            try {
                $disk->put($relative, $stream);
            } finally {
                fclose($stream);
            }
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function backupPath(string $name): string
    {
        if (! $this->isValidName($name)) {
            throw new InvalidArgumentException("Invalid backup name [{$name}].");
        }

        $disk = $this->localDisk();
        $path = self::BACKUP_DIRECTORY.'/'.$name;

        if (! $disk->exists($path)) {
            throw new InvalidArgumentException("Backup [{$name}] does not exist.");
        }

        return $disk->path($path);
    }

    /**
     * Name collisions within one second get an incrementing suffix instead of
     * silently overwriting an earlier backup.
     */
    private function uniqueName(): string
    {
        $base = 'backup-'.now()->format('Y-m-d-His');
        $name = $base.'.zip';

        for ($attempt = 2; $this->localDisk()->exists(self::BACKUP_DIRECTORY.'/'.$name); $attempt++) {
            $name = $base.'-'.$attempt.'.zip';
        }

        return $name;
    }

    private function isValidName(string $name): bool
    {
        return preg_match(self::FILENAME_PATTERN, $name) === 1;
    }

    private function isValidTableName(string $table): bool
    {
        return preg_match('/^[A-Za-z0-9_]+$/', $table) === 1;
    }

    /**
     * Reject archive entries that could escape the media root (zip-slip).
     */
    private function isSafeRelativePath(string $path): bool
    {
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return false;
            }
        }

        return true;
    }

    private function disableForeignKeyChecks(): void
    {
        match (DB::getDriverName()) {
            'mysql', 'mariadb' => DB::statement('SET FOREIGN_KEY_CHECKS=0'),
            'sqlite' => $this->disableSqliteForeignKeyChecks(),
            // Other drivers keep their checks; restore order then relies on
            // the dump containing parents before children (alphabetical).
            default => null,
        };
    }

    private function enableForeignKeyChecks(): void
    {
        match (DB::getDriverName()) {
            'mysql', 'mariadb' => DB::statement('SET FOREIGN_KEY_CHECKS=1'),
            'sqlite' => DB::statement('PRAGMA foreign_keys = ON'),
            default => null,
        };
    }

    private function disableSqliteForeignKeyChecks(): void
    {
        // foreign_keys is only settable outside a transaction, so inside one
        // (e.g. a test wrapped by RefreshDatabase) it is a silent no-op. The
        // deferred pragma is transaction-safe and pushes every violation to
        // commit time, by which point all dump rows are restored.
        DB::statement('PRAGMA foreign_keys = OFF');
        DB::statement('PRAGMA defer_foreign_keys = ON');
    }

    /**
     * @return array<int, string>
     */
    private function userTableNames(): array
    {
        return array_values(array_filter(
            Schema::getTableListing(schemaQualified: false),
            fn (string $table): bool => ! in_array($table, self::EXCLUDED_TABLES, true),
        ));
    }

    private function localDisk(): Filesystem
    {
        return Storage::disk('local');
    }

    private function mediaDisk(): Filesystem
    {
        return Storage::disk((string) config('media.disk', 'public'));
    }
}
