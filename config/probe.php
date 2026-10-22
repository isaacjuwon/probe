<?php

declare(strict_types=1);

return [
    'enabled' => env(key: 'PROBE_ENABLED', default: true),
    'path' => 'probe',
    'middleware' => ['web'],
    'allowed_environments' => ['local', 'testing'],
    'allowed_hosts' => [],
    'transport' => env(key: 'PROBE_TRANSPORT', default: 'in_process'),
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
