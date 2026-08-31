<?php

namespace Sitewyn\Core\Base\Support;

class RobotsTxt
{
    public const DEFAULT_CONTENT = "User-agent: *\nDisallow: /admin\n";

    /**
     * The configured robots.txt body; blank or unset falls back to the default.
     */
    public static function content(?string $configured): string
    {
        $configured = trim((string) $configured);

        return $configured !== '' ? $configured : self::DEFAULT_CONTENT;
    }
}
