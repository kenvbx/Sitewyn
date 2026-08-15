<?php

use Sitewyn\Core\Base\Providers\BaseServiceProvider;

return [
    'name' => 'Sitewyn Core Base',
    'modules' => [
        'provider_roots' => [
            'platform/core',
            'platform/packages',
            'platform/plugins',
            'platform/themes',
        ],
        'excluded_providers' => [
            BaseServiceProvider::class,
        ],
    ],
    'admin' => [
        'name' => env('SITEWYN_ADMIN_NAME'),
        'username' => env('SITEWYN_ADMIN_USERNAME'),
        'email' => env('SITEWYN_ADMIN_EMAIL'),
        'password' => env('SITEWYN_ADMIN_PASSWORD'),
    ],
];
