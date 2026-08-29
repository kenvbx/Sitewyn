<?php

namespace Sitewyn\Packages\Media\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Sitewyn\Packages\Media\Models\MediaFolder;

class MediaFolderRepository
{
    /**
     * @return Collection<int, MediaFolder>
     */
    public function allForSelect()
    {
        return MediaFolder::query()
            ->orderBy('path')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, MediaFolder>
     */
    public function childrenOf(?int $parentId = null)
    {
        return MediaFolder::query()
            ->where('parent_id', $parentId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, MediaFolder>
     */
    public function searchByName(string $term, ?int $parentId = null)
    {
        return MediaFolder::query()
            ->when($parentId !== null, fn ($query) => $query->where('parent_id', $parentId))
            ->where('name', 'like', '%'.$term.'%')
            ->orderBy('name')
            ->get();
    }

    public function findByPath(?string $path): ?MediaFolder
    {
        return MediaFolder::query()
            ->where('path', $path)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): MediaFolder
    {
        $attributes['slug'] ??= $this->uniqueSlug(
            Str::slug((string) $attributes['name']) ?: 'folder',
            $attributes['parent_id'] ?? null,
        );
        $attributes['path'] ??= $this->pathFor(
            $attributes['slug'],
            $attributes['parent_id'] ?? null,
        );

        return MediaFolder::query()->create($attributes);
    }

    public function rename(MediaFolder $folder, string $name): MediaFolder
    {
        return DB::transaction(function () use ($folder, $name): MediaFolder {
            $folder->update([
                'name' => $name,
                'slug' => $this->uniqueSlug(Str::slug($name) ?: 'folder', $folder->parent_id, $folder->id),
            ]);

            $this->refreshPathTree($folder->refresh());

            return $folder->refresh();
        });
    }

    public function move(MediaFolder $folder, ?int $parentId): MediaFolder
    {
        return DB::transaction(function () use ($folder, $parentId): MediaFolder {
            $folder->update([
                'parent_id' => $parentId,
                'slug' => $this->uniqueSlug($folder->slug, $parentId, $folder->id),
            ]);

            $this->refreshPathTree($folder->refresh());

            return $folder->refresh();
        });
    }

    public function deleteTree(MediaFolder $folder, MediaFileRepository $files): void
    {
        DB::transaction(function () use ($folder, $files): void {
            $folder->load(['children', 'files']);

            foreach ($folder->children as $child) {
                $this->deleteTree($child, $files);
            }

            foreach ($folder->files as $file) {
                $files->deleteWithFiles($file);
            }

            $folder->delete();
        });
    }

    public function isDescendantOf(MediaFolder $folder, MediaFolder $ancestor): bool
    {
        $parent = $folder->parent;

        while ($parent) {
            if ($parent->is($ancestor)) {
                return true;
            }

            $parent = $parent->parent;
        }

        return false;
    }

    private function pathFor(string $slug, mixed $parentId): string
    {
        if (! $parentId) {
            return $slug;
        }

        $parent = MediaFolder::query()->find($parentId);

        return trim(($parent?->path ? $parent->path.'/' : '').$slug, '/');
    }

    private function uniqueSlug(string $slug, mixed $parentId, ?int $exceptId = null): string
    {
        $candidate = $slug;
        $suffix = 2;

        while (MediaFolder::query()
            ->where('parent_id', $parentId)
            ->where('slug', $candidate)
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->exists()) {
            $candidate = $slug.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function refreshPathTree(MediaFolder $folder): void
    {
        $folder->update(['path' => $this->pathFor($folder->slug, $folder->parent_id)]);

        $folder->children()
            ->get()
            ->each(fn (MediaFolder $child) => $this->refreshPathTree($child));
    }
}
