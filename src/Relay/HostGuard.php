<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Relay;

use Closure;
use Isaacjuwon\Probe\Relay\Exceptions\HostNotAllowedException;

final class HostGuard
{
    /**
     * @param  list<string>  $allowedHosts
     * @param  (Closure(): ?string)|null  $currentHostResolver
     */
    public function __construct(
        private array $allowedHosts = [],
        private ?string $appUrl = null,
        private ?Closure $currentHostResolver = null,
    ) {}

    public function isAllowed(string $url): bool
    {
        if (! str_contains(haystack: $url, needle: '://')) {
            $url = 'http://'.$url;
        }

        $host = parse_url(url: $url, component: PHP_URL_HOST);

        if ($host === null || $host === false) {
            return false;
        }

        $host = strtolower(string: $host);

        $allowedLower = array_map(
            callback: static fn (string $h): string => strtolower(string: $h),
            array: $this->allowedHosts,
        );

        if (in_array(needle: $host, haystack: $allowedLower, strict: true)) {
            return true;
        }

        $appHost = $this->appUrl !== null
            ? parse_url(url: $this->appUrl, component: PHP_URL_HOST)
            : null;

        if ($appHost !== null && $appHost !== false && strcasecmp(string1: $host, string2: (string) $appHost) === 0) {
            return true;
        }

        if ($this->currentHostResolver !== null) {
            $currentHost = ($this->currentHostResolver)();

            if ($currentHost !== null && strcasecmp(string1: $host, string2: $currentHost) === 0) {
                return true;
            }
        }

        $defaultLocalHosts = ['localhost', '127.0.0.1', '::1', 'host.docker.internal'];

        if (in_array(needle: $host, haystack: $defaultLocalHosts, strict: true)) {
            return true;
        }

        // Allow standard local development TLDs
        if (str_ends_with(haystack: $host, needle: '.test') ||
            str_ends_with(haystack: $host, needle: '.local') ||
            str_ends_with(haystack: $host, needle: '.localhost')) {
            return true;
        }

        // Allow LAN / private IP ranges
        if (filter_var(value: $host, filter: FILTER_VALIDATE_IP) !== false
            && filter_var(value: $host, filter: FILTER_VALIDATE_IP, options: FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return true;
        }

        return false;
    }

    public function ensureAllowed(string $url): void
    {
        if (! $this->isAllowed(url: $url)) {
            throw new HostNotAllowedException(
                message: sprintf('Host for URL [%s] is not permitted by Probe security configuration. Add the host to config/probe.php allowed_hosts.', $url),
            );
        }
    }
}
