<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Relay;

use Isaacjuwon\Probe\Relay\Exceptions\HostNotAllowedException;

final class HostGuard
{
    /**
     * @param  list<string>  $allowedHosts
     */
    public function __construct(
        private array $allowedHosts = [],
        private ?string $appUrl = null,
    ) {}

    public function isAllowed(string $url): bool
    {
        $host = parse_url(url: $url, component: PHP_URL_HOST);

        if ($host === null || $host === false) {
            return false;
        }

        if (in_array(needle: $host, haystack: $this->allowedHosts, strict: true)) {
            return true;
        }

        $appHost = $this->appUrl !== null
            ? parse_url(url: $this->appUrl, component: PHP_URL_HOST)
            : null;

        if ($appHost !== null && $appHost !== false && strcasecmp(string1: $host, string2: (string) $appHost) === 0) {
            return true;
        }

        $defaultLocalHosts = ['localhost', '127.0.0.1', '::1'];

        return in_array(needle: strtolower(string: $host), haystack: $defaultLocalHosts, strict: true);
    }

    public function ensureAllowed(string $url): void
    {
        if (! $this->isAllowed(url: $url)) {
            throw new HostNotAllowedException(
                message: sprintf('Host for URL [%s] is not permitted by Probe security configuration.', $url),
            );
        }
    }
}
