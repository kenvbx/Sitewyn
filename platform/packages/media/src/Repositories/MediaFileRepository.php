<?php

namespace Sitewyn\Packages\Media\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Sitewyn\Packages\Media\Models\MediaFile;
use Sitewyn\Packages\Media\Support\MediaStorage;

class MediaFileRepository
{
    /**
     * @return Collection<int, MediaFile>
     */
    public function inFolder(?int $folderId = null)
    {
        return MediaFile::query()
            ->where('folder_id', $folderId)
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, MediaFile>
     */
    public function search(string $term, ?int $folderId = null)
    {
        return MediaFile::query()
            ->when($folderId !== null, fn ($query) => $query->where('folder_id', $folderId))
            ->where(function ($query) use ($term): void {
                $query
                    ->where('name', 'like', '%'.$term.'%')
                    ->orWhere('file_name', 'like', '%'.$term.'%')
                    ->orWhere('path', 'like', '%'.$term.'%');
            })
            ->orderBy('name')
            ->get();
    }

    public function findByPath(string $path): ?MediaFile
    {
        return MediaFile::query()
            ->where('path', $path)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): MediaFile
    {
        $attributes['disk'] ??= 'public';

        return MediaFile::query()->create($attributes);
    }

    public function rename(MediaFile $file, string $name): MediaFile
    {
        $file->update(['name' => $name]);

        return $file->refresh();
    }

    public function move(MediaFile $file, ?int $folderId): MediaFile
    {
        $file->update(['folder_id' => $folderId]);

        return $file->refresh();
    }

    public function deleteWithFiles(MediaFile $file): void
    {
        $storage = app(MediaStorage::class);

        $paths = collect([['disk' => $file->disk, 'path' => $file->path]])
            ->merge($this->conversionPaths($file->conversions ?? []))
            ->filter(fn (array $file): bool => is_string($file['path'] ?? null) && $file['path'] !== '')
            ->unique(fn (array $file): string => ($file['disk'] ?? '').':'.$file['path']);

        $paths->each(fn (array $file): bool => $storage->delete($file['path'], $file['disk'] ?? null));

        $file->delete();
    }

    /**
     * @param  array<mixed>  $conversions
     * @return array<int, array{disk: string|null, path: string|null}>
     */
    private function conversionPaths(array $conversions): array
    {
        $paths = [];

        foreach ($conversions as $conversion) {
            if (is_array($conversion)) {
                if (isset($conversion['path']) && is_string($conversion['path'])) {
                    $paths[] = [
                        'disk' => isset($conversion['disk']) && is_string($conversion['disk']) ? $conversion['disk'] : null,
                        'path' => $conversion['path'],
                    ];
                }

                $paths = array_merge($paths, $this->conversionPaths($conversion));
            }
        }

        return $paths;
    }
}
