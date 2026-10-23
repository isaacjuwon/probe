<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Discovery;

use Illuminate\Routing\Route;
use Isaacjuwon\Probe\Enums\AuthType;

final class RouteAuthDetector
{
    public function __construct(
        private readonly ControllerReflector $reflector = new ControllerReflector,
    ) {}

    /**
     * @param  array<string>  $middleware
     */
    public function detect(Route $route, array $middleware): AuthType
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
}
