<?php

namespace Sitewyn\Packages\Media\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Exceptions\ImageException;
use Intervention\Image\ImageManager;

class ImageConversionGenerator
{
    public function __construct(private readonly MediaStorage $storage) {}

    /**
     * @return array<string, array<string, mixed>>
     */
    public function generate(UploadedFile $file, string $disk, string $originalPath): array
    {
        if (! $this->supports($file)) {
            return [];
        }

        $manager = new ImageManager(new Driver);
        $conversions = [];

        foreach (config('media.image_conversions', []) as $name => $settings) {
            try {
                $image = $manager->decodePath($file->getRealPath());
                $width = (int) ($settings['width'] ?? 0);
                $height = isset($settings['height']) ? (int) $settings['height'] : null;

                if (($settings['mode'] ?? null) === 'cover' && $width > 0 && $height > 0) {
                    $image->cover($width, $height);
                } else {
                    $image->scaleDown($width > 0 ? $width : null, $height);
                }

                $path = $this->conversionPath($originalPath, (string) $name);
                $this->storage->put($path, (string) $image->encodeUsingFileExtension(pathinfo($path, PATHINFO_EXTENSION)), $disk);

                $conversions[(string) $name] = [
                    'path' => $path,
                    'disk' => $disk,
                    'width' => $image->width(),
                    'height' => $image->height(),
                    'url' => $this->storage->url($disk, $path),
                ];
            } catch (ImageException) {
                continue;
            }
        }

        return $conversions;
    }

    private function supports(UploadedFile $file): bool
    {
        return in_array($file->getMimeType(), [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
        ], true);
    }

    private function conversionPath(string $originalPath, string $conversion): string
    {
        $directory = trim(pathinfo($originalPath, PATHINFO_DIRNAME), '.');
        $extension = pathinfo($originalPath, PATHINFO_EXTENSION) ?: 'jpg';
        $name = pathinfo($originalPath, PATHINFO_FILENAME);

        return trim($directory.'/conversions/'.$name.'-'.Str::slug($conversion).'.'.$extension, '/');
    }
}
