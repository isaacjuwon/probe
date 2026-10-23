<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Discovery;

use Illuminate\Routing\Route;

final class RouteVersionDetector
{
    public function __construct(
        private readonly ControllerReflector $reflector = new ControllerReflector,
    ) {}

    /**
     * Detect API version following standard REST and Laravel API versioning patterns:
     * - URI path segment (/api/v1/..., /v2.0/..., /2026-10-01/...)
     * - Route prefix ($route->getPrefix())
     * - Route name prefix (v1.orders.index, api.v2.users)
     * - Controller namespace (\Api\V1\UserController)
     * - Route middleware (version:v1, api-version:v2)
     * - Controller docblock (@version v1)
     *
     * @param  array<string>  $middleware
     */
    public function detect(Route $route, string $uri, array $middleware): ?string
    {
        // 1. URI path segment (e.g. /api/v1/..., /v2.1/..., /v1-beta/...)
        if (preg_match(pattern: '/(?:\/|^)(v\d+(?:\.\d+)?(?:-[a-z0-9]+)?)(?:\/|$)/i', subject: $uri, matches: $matches)) {
            return strtolower(string: $matches[1]);
        }

        // Date-based URI version (e.g. /api/2026-10-01/...)
        if (preg_match(pattern: '/(?:\/|^)(\d{4}-\d{2}-\d{2})(?:\/|$)/', subject: $uri, matches: $matches)) {
            return $matches[1];
        }

        // 2. Route Prefix (e.g. v1, api/v2)
        $prefix = $route->getPrefix();

        if ($prefix !== null) {
            if (preg_match(pattern: '/(?:\/|^)(v\d+(?:\.\d+)?(?:-[a-z0-9]+)?)(?:\/|$)/i', subject: $prefix, matches: $matches)) {
                return strtolower(string: $matches[1]);
            }

            if (preg_match(pattern: '/(?:\/|^)(\d{4}-\d{2}-\d{2})(?:\/|$)/', subject: $prefix, matches: $matches)) {
                return $matches[1];
            }
        }

        // 3. Route Name Prefix (e.g. v1.orders.index, api.v2.users)
        $routeName = $route->getName();

        if ($routeName !== null && preg_match(pattern: '/(?:^|\.)(v\d+(?:\.\d+)?(?:-[a-z0-9]+)?)(?:\.|$)/i', subject: $routeName, matches: $matches)) {
            return strtolower(string: $matches[1]);
        }

        // 4. Controller Action Namespace (e.g. App\Http\Controllers\Api\V1\UserController)
        $action = $route->getActionName();

        if (preg_match(pattern: '/(?:^|\\\\)v(\d+(?:\.\d+)?)(?:\\\\|$)/i', subject: $action, matches: $matches)) {
            return 'v'.strtolower(string: $matches[1]);
        }

        // 5. Route Middleware (e.g. version:v1, api-version:v2, v1)
        foreach ($middleware as $m) {
            if (preg_match(pattern: '/(?:version|api-version|v)[:=]([a-z0-9.-]+)/i', subject: $m, matches: $matches)) {
                $ver = strtolower(string: $matches[1]);

                return str_starts_with(haystack: $ver, needle: 'v') ? $ver : 'v'.$ver;
            }
        }

        // 6. Controller DocBlock @version
        $reflection = $this->reflector->getReflection(action: $route->getAction(key: 'uses'));

        if ($reflection !== null) {
            $docComment = $reflection->getDocComment();

            if (is_string(value: $docComment) && preg_match(pattern: '/@version\s+([a-z0-9.-]+)/i', subject: $docComment, matches: $matches)) {
                $ver = strtolower(string: $matches[1]);

                return str_starts_with(haystack: $ver, needle: 'v') ? $ver : 'v'.$ver;
            }
        }

        return null;
    }
}
