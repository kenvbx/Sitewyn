<?php

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
            Sitewyn\Core\Base\Providers\BaseServiceProvider::class,
        ],
    ],
];
