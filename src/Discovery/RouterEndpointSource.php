<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Discovery;

use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route as RouteFacade;
use Isaacjuwon\Probe\Contracts\EndpointSource;
use Isaacjuwon\Probe\Data\Endpoint;
use Isaacjuwon\Probe\Data\FieldSchema;
use Isaacjuwon\Probe\Enums\AuthType;
use Isaacjuwon\Probe\Enums\HttpMethod;
use Isaacjuwon\Probe\Schema\RuleSchemaBuilder;

/**
 * Strategy: Discovers endpoints from the Laravel Router.
 */
final class RouterEndpointSource implements EndpointSource
{
    /**
     * @param  list<string>  $prefixes
     * @param  list<string>  $middleware
     */
    public function __construct(
        private readonly ControllerReflector $reflector = new ControllerReflector,
        private readonly FormRequestRulesExtractor $formRequestExtractor = new FormRequestRulesExtractor,
        private readonly InlineValidationExtractor $inlineExtractor = new InlineValidationExtractor,
        private readonly SpatieDataRulesExtractor $spatieExtractor = new SpatieDataRulesExtractor,
        private readonly RuleSchemaBuilder $schemaBuilder = new RuleSchemaBuilder,
        private readonly array $prefixes = ['api'],
        private readonly array $middleware = ['api'],
        private readonly string $probePath = 'probe',
    ) {}

    /**
     * @return Collection<int, Endpoint>
     */
    public function endpoints(): Collection
    {
        $allRoutes = RouteFacade::getRoutes()->getRoutes();
        $endpoints = [];

        foreach ($allRoutes as $route) {
            if ($this->shouldIgnoreRoute(route: $route)) {
                continue;
            }

            if (! $this->matchesFilters(route: $route)) {
                continue;
            }

            $endpoint = $this->createEndpoint(route: $route);

            if ($endpoint !== null) {
                $endpoints[] = $endpoint;
            }
        }

        return new Collection(items: $endpoints);
    }

    private function shouldIgnoreRoute(Route $route): bool
    {
        $uri = ltrim(string: $route->uri(), characters: '/');

        if ($uri === $this->probePath || str_starts_with(haystack: $uri, needle: $this->probePath.'/')) {
            return true;
        }

        if (str_starts_with(haystack: $uri, needle: '_ignition') || str_starts_with(haystack: $uri, needle: 'sanctum/csrf-cookie')) {
            return true;
        }

        return false;
    }

    private function matchesFilters(Route $route): bool
    {
        $uri = ltrim(string: $route->uri(), characters: '/');

        if ($this->prefixes !== []) {
            $matchedPrefix = false;

            foreach ($this->prefixes as $prefix) {
                $cleanPrefix = trim(string: $prefix, characters: '/');

                if ($cleanPrefix === '' || $uri === $cleanPrefix || str_starts_with(haystack: $uri, needle: $cleanPrefix.'/')) {
                    $matchedPrefix = true;

                    break;
                }
            }

            if (! $matchedPrefix) {
                return false;
            }
        }

        if ($this->middleware !== []) {
            $routeMiddleware = $route->gatherMiddleware();
            $hasMatchingMiddleware = false;

            foreach ($this->middleware as $filterMiddleware) {
                if (in_array(needle: $filterMiddleware, haystack: $routeMiddleware, strict: true)) {
                    $hasMatchingMiddleware = true;

                    break;
                }
            }

            if (! $hasMatchingMiddleware) {
                return false;
            }
        }

        return true;
    }

    private function createEndpoint(Route $route): ?Endpoint
    {
        $method = $this->resolveMethod(methods: $route->methods());

        if ($method === null) {
            return null;
        }

        $uri = $route->uri();
        $routeMiddleware = array_values(array: $route->gatherMiddleware());
        $authType = $this->detectAuthType(route: $route, middleware: $routeMiddleware);
        $version = $this->detectVersion(route: $route, uri: $uri, middleware: $routeMiddleware);
        $parameters = $this->extractRouteParameters(uri: $uri);
        $fields = $this->extractFields(route: $route);

        return new Endpoint(
            method: $method,
            uri: $uri,
            name: $route->getName(),
            action: $route->getActionName(),
            middleware: $routeMiddleware,
            headers: [
                'Accept' => 'application/json',
            ],
            parameters: $parameters,
            fields: $fields,
            group: $this->resolveGroup(uri: $uri, version: $version),
            description: null,
            auth: $authType,
            version: $version,
        );
    }

    /**
     * @param  array<string>  $methods
     */
    private function resolveMethod(array $methods): ?HttpMethod
    {
        $normalized = array_filter(
            array: $methods,
            callback: static fn (string $m): bool => $m !== 'HEAD',
        );

        $first = reset($normalized);

        if (! $first) {
            return null;
        }

        return HttpMethod::tryFrom(value: strtoupper(string: $first));
    }

    /**
     * @param  array<string>  $middleware
     */
    private function detectAuthType(Route $route, array $middleware): AuthType
    {
        foreach ($middleware as $m) {
            $lower = strtolower(string: $m);

            if (str_contains(haystack: $lower, needle: 'sanctum')) {
                return AuthType::SanctumActAs;
            }

            if (str_contains(haystack: $lower, needle: 'api_key') || str_contains(haystack: $lower, needle: 'apikey') || str_contains(haystack: $lower, needle: 'api-key')) {
                return AuthType::ApiKey;
            }

            if (str_contains(haystack: $lower, needle: 'auth:api') || str_contains(haystack: $lower, needle: 'bearer')) {
                return AuthType::Bearer;
            }

            if (str_contains(haystack: $lower, needle: 'auth:basic') || str_contains(haystack: $lower, needle: 'basic')) {
                return AuthType::Basic;
            }

            if ($m === 'auth' || str_starts_with(haystack: $m, needle: 'auth:')) {
                return AuthType::Session;
            }
        }

        $action = $route->getActionName();
        $actionLower = strtolower(string: $action);

        if (str_contains(haystack: $actionLower, needle: 'apikey') || str_contains(haystack: $actionLower, needle: 'api_key')) {
            return AuthType::ApiKey;
        }

        if (str_contains(haystack: $actionLower, needle: 'basicauth')) {
            return AuthType::Basic;
        }

        $reflection = $this->reflector->getReflection(action: $route->getAction(key: 'uses'));

        if ($reflection !== null) {
            $docComment = $reflection->getDocComment();

            if (is_string(value: $docComment)) {
                $docLower = strtolower(string: $docComment);

                if (str_contains(haystack: $docLower, needle: 'x-api-key') || str_contains(haystack: $docLower, needle: 'api_key')) {
                    return AuthType::ApiKey;
                }

                if (str_contains(haystack: $docLower, needle: '@authenticated')) {
                    return AuthType::SanctumActAs;
                }
            }
        }

        return AuthType::None;
    }

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
    private function detectVersion(Route $route, string $uri, array $middleware): ?string
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

    /**
     * @return array<string, mixed>
     */
    private function extractRouteParameters(string $uri): array
    {
        $parameters = [];

        if (preg_match_all(pattern: '/\{(?P<param>[a-zA-Z_][a-zA-Z0-9_-]*)(?P<optional>\?)?\}/', subject: $uri, matches: $matches, flags: PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $parameters[$match['param']] = [
                    'name' => $match['param'],
                    'required' => empty($match['optional']),
                    'example' => 1,
                ];
            }
        }

        return $parameters;
    }

    /**
     * @return array<string, FieldSchema>
     */
    private function extractFields(Route $route): array
    {
        $action = $route->getAction(key: 'uses');
        $rawRules = [];

        $formRequestClass = $this->reflector->getFormRequestClass(action: $action);

        if ($formRequestClass !== null) {
            $rawRules = $this->formRequestExtractor->extract(class: $formRequestClass);
        }

        if ($rawRules === []) {
            $reflection = $this->reflector->getReflection(action: $action);
            $rawRules = $this->inlineExtractor->extract(reflection: $reflection);

            if ($rawRules === []) {
                $rawRules = $this->spatieExtractor->extract(reflection: $reflection);
            }
        }

        $fields = [];

        foreach ($rawRules as $field => $rules) {
            $fields[(string) $field] = $this->schemaBuilder->build(
                field: (string) $field,
                rules: $rules,
            );
        }

        return $fields;
    }

    private function resolveGroup(string $uri, ?string $version = null): string
    {
        $segments = array_values(array: array_filter(
            array: explode(separator: '/', string: trim(string: $uri, characters: '/')),
            callback: static fn (string $segment): bool => $segment !== '',
        ));

        if ($segments === []) {
            return 'General';
        }

        if (strtolower(string: $segments[0]) === 'api') {
            array_shift(array: $segments);
        }

        if ($segments === []) {
            return 'Api';
        }

        $first = strtolower(string: $segments[0]);

        if (($version !== null && $first === strtolower(string: $version)) || preg_match(pattern: '/^v\d+(\.\d+)?(-[a-z0-9]+)?$/i', subject: $first) || preg_match(pattern: '/^\d{4}-\d{2}-\d{2}$/', subject: $first)) {
            array_shift(array: $segments);
        }

        if ($segments === []) {
            return $version !== null ? ucfirst(string: $version) : 'General';
        }

        return ucfirst(string: $segments[0]);
    }
}
