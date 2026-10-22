<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Support;

use Illuminate\Support\Manager;
use Isaacjuwon\Probe\Contracts\EndpointSource;
use Isaacjuwon\Probe\Discovery\NullScribeSource;
use Isaacjuwon\Probe\Discovery\RouterEndpointSource;
use Isaacjuwon\Probe\Discovery\ScribeSource;

/**
 * Manager: Driver resolution for endpoint sources.
 */
final class SourceManager extends Manager
{
    public function getDefaultDriver(): string
    {
        /** @var string $driver */
        $driver = $this->config->get(key: 'probe.sources.0', default: 'router');

        return $driver;
    }

    public function createRouterDriver(): EndpointSource
    {
        /** @var list<string> $prefixes */
        $prefixes = $this->config->get(key: 'probe.route_filters.prefixes', default: ['api']);

        /** @var list<string> $middleware */
        $middleware = $this->config->get(key: 'probe.route_filters.middleware', default: ['api']);

        /** @var string $probePath */
        $probePath = $this->config->get(key: 'probe.path', default: 'probe');

        return new RouterEndpointSource(
            prefixes: $prefixes,
            middleware: $middleware,
            probePath: $probePath,
        );
    }

    public function createScribeDriver(): EndpointSource
    {
        /** @var string $enabled */
        $enabled = $this->config->get(key: 'probe.scribe.enabled', default: 'auto');

        if ($enabled === 'false') {
            return new NullScribeSource;
        }

        /** @var string|null $path */
        $path = $this->config->get(key: 'probe.scribe.path');

        return new ScribeSource(basePath: $path);
    }
}
