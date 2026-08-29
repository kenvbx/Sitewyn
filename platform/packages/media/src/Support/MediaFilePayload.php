<?php

namespace Sitewyn\Packages\Media\Support;

use Sitewyn\Packages\Media\Models\MediaFile;

class MediaFilePayload
{
    /**
     * @return array<string, mixed>
     */
    public static function make(MediaFile $file): array
    {
        $conversions = $file->conversions ?? [];
        $thumbnailDisk = data_get($conversions, 'thumb.disk', $file->disk);
        $thumbnailPath = data_get($conversions, 'thumb.path');
        $thumbnailUrl = data_get($conversions, 'thumb.url');

        return [
            'id' => $file->id,
            'folder_id' => $file->folder_id,
            'name' => $file->name,
            'file_name' => $file->file_name,
            'mime_type' => $file->mime_type,
            'size' => $file->size,
            'dimensions' => $file->width && $file->height ? $file->width.'x'.$file->height : null,
            'url' => app(MediaStorage::class)->url($file->disk, $file->path),
            'thumbnail' => is_string($thumbnailPath)
                ? app(MediaStorage::class)->url((string) $thumbnailDisk, $thumbnailPath)
                : (is_string($thumbnailUrl) ? app(MediaStorage::class)->normalizeUrl($thumbnailUrl) : null),
            'is_image' => str_starts_with((string) $file->mime_type, 'image/'),
            'created_at' => $file->created_at,
        ];
    }
}
