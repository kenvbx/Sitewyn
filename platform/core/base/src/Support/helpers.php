<?php

use Sitewyn\Core\Base\Support\AdminFlash;
use Sitewyn\Core\Base\Support\SettingStore;

if (! function_exists('admin_flash')) {
    function admin_flash(): AdminFlash
    {
        return app(AdminFlash::class);
    }
}

if (! function_exists('site_setting')) {
    function site_setting(string $key, ?string $default = null): ?string
    {
        return app(SettingStore::class)->get($key, $default);
    }
}
