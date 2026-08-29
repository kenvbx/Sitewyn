<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Sitewyn\Packages\Media\Models\MediaFile;
use Sitewyn\Packages\Media\Models\MediaFolder;
use Sitewyn\Packages\Media\Repositories\MediaFileRepository;
use Sitewyn\Packages\Media\Repositories\MediaFolderRepository;
use Tests\TestCase;

class MediaRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_folder_repository_can_create_nested_folders_and_list_children(): void
    {
        $repository = new MediaFolderRepository;

        $root = $repository->create([
            'name' => 'Uploads',
        ]);
        $child = $repository->create([
            'parent_id' => $root->id,
            'name' => 'Hero Images',
        ]);

        $this->assertSame('uploads', $root->slug);
        $this->assertSame('uploads', $root->path);
        $this->assertSame('hero-images', $child->slug);
        $this->assertSame('uploads/hero-images', $child->path);
        $this->assertTrue($root->children->contains($child));
        $this->assertSame([$child->id], $repository->childrenOf($root->id)->pluck('id')->all());
        $this->assertSame($child->id, $repository->findByPath('uploads/hero-images')?->id);
    }

    public function test_folder_repository_can_search_by_name(): void
    {
        $repository = new MediaFolderRepository;
        $root = MediaFolder::query()->create([
            'name' => 'Root',
            'slug' => 'root',
            'path' => 'root',
        ]);
        MediaFolder::query()->create([
            'parent_id' => $root->id,
            'name' => 'Product Photos',
            'slug' => 'product-photos',
            'path' => 'root/product-photos',
        ]);
        MediaFolder::query()->create([
            'name' => 'Documents',
            'slug' => 'documents',
            'path' => 'documents',
        ]);

        $this->assertSame(['Product Photos'], $repository->searchByName('Photo', $root->id)->pluck('name')->all());
    }

    public function test_file_repository_can_create_and_query_files_by_folder(): void
    {
        $repository = new MediaFileRepository;
        $folder = MediaFolder::query()->create([
            'name' => 'Images',
            'slug' => 'images',
            'path' => 'images',
        ]);

        $file = $repository->create([
            'folder_id' => $folder->id,
            'name' => 'Hero',
            'file_name' => 'hero.jpg',
            'path' => '2026/08/hero.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'width' => 1200,
            'height' => 630,
        ]);

        $this->assertSame('public', $file->disk);
        $this->assertSame($folder->id, $file->folder?->id);
        $this->assertSame([$file->id], $repository->inFolder($folder->id)->pluck('id')->all());
        $this->assertSame($file->id, $repository->findByPath('2026/08/hero.jpg')?->id);
    }

    public function test_file_repository_can_search_by_name_file_name_or_path(): void
    {
        $repository = new MediaFileRepository;
        $folder = MediaFolder::query()->create([
            'name' => 'Images',
            'slug' => 'images',
            'path' => 'images',
        ]);
        $match = MediaFile::query()->create([
            'folder_id' => $folder->id,
            'name' => 'Homepage Banner',
            'file_name' => 'banner.jpg',
            'path' => '2026/08/banner.jpg',
            'disk' => 'public',
        ]);
        MediaFile::query()->create([
            'name' => 'Document',
            'file_name' => 'document.pdf',
            'path' => '2026/08/document.pdf',
            'disk' => 'public',
        ]);

        $this->assertSame([$match->id], $repository->search('banner', $folder->id)->pluck('id')->all());
        $this->assertSame([$match->id], $repository->search('Homepage')->pluck('id')->all());
    }
}
