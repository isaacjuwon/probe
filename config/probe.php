<?php

declare(strict_types=1);

return [
    'enabled' => env(key: 'PROBE_ENABLED', default: true),
    'path' => 'probe',
    'middleware' => ['web'],
    'allowed_environments' => ['local'],
    'allowed_hosts' => [],
    'transport' => 'http',
    'sources' => ['router', 'scribe'],
    'route_filters' => [
        'prefixes' => ['api'],
        'middleware' => ['api'],
    ],
    'scribe' => [
        'enabled' => 'auto',
        'path' => null,
    ],
    'storage' => [
        'disk' => 'local',
        'path' => 'probe',
    ],
];
