<?php

namespace Sitewyn\Packages\Media\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MediaStorage
{
    public function diskName(?string $disk = null): string
    {
        return $disk ?: (string) config('media.disk', 'public');
    }

    public function disk(?string $disk = null): Filesystem
    {
        return Storage::disk($this->diskName($disk));
    }

    public function uploadDirectory(): string
    {
        return trim(now()->format((string) config('media.upload_directory_format', 'Y/m')), '/');
    }

    public function putFileAs(UploadedFile $file, string $name, ?string $disk = null, ?string $directory = null): string
    {
        return $this->disk($disk)->putFileAs($directory ?? $this->uploadDirectory(), $file, $name);
    }

    public function put(string $path, string $contents, ?string $disk = null): bool
    {
        return $this->disk($disk)->put($path, $contents);
    }

    public function delete(string $path, ?string $disk = null): bool
    {
        return $this->disk($disk)->delete($path);
    }

    public function url(?string $disk, string $path): string
    {
        $disk = $this->diskName($disk);
        $config = config("filesystems.disks.{$disk}", []);

        if (($config['driver'] ?? null) === 'local' && ($config['root'] ?? null) === storage_path('app/public')) {
            return '/storage/'.ltrim($path, '/');
        }

        return Storage::disk($disk)->url($path);
    }

    public function normalizeUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (is_string($path) && str_starts_with($path, '/storage/')) {
            return $path;
        }

        return $url;
    }
}
