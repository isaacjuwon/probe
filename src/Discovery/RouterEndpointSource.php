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
            group: $this->resolveGroup(uri: $uri),
            description: null,
            auth: $authType,
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

    private function resolveGroup(string $uri): string
    {
        $parts = explode(separator: '/', string: trim(string: $uri, characters: '/'));

        if ($parts[0] === 'api' && isset($parts[1])) {
            return ucfirst(string: $parts[1]);
        }

        return ucfirst(string: $parts[0]);
    }
}
