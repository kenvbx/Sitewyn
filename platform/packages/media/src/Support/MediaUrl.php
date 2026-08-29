<?php

namespace Sitewyn\Packages\Media\Support;

class MediaUrl
{
    public static function make(string $disk, string $path): string
    {
        return app(MediaStorage::class)->url($disk, $path);
    }

    public static function normalize(string $url): string
    {
        return app(MediaStorage::class)->normalizeUrl($url);
    }
}
