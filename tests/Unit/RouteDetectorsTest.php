<?php

declare(strict_types=1);

use Illuminate\Routing\Route;
use Isaacjuwon\Probe\Discovery\RouteAuthDetector;
use Isaacjuwon\Probe\Discovery\RouteVersionDetector;
use Isaacjuwon\Probe\Enums\AuthType;

it('detects route auth type from various middleware patterns', function (): void {
    $detector = new RouteAuthDetector;
    $route = new Route(methods: ['GET'], uri: 'api/items', action: ['uses' => static fn (): string => 'ok']);

    expect($detector->detect(route: $route, middleware: ['auth:sanctum']))->toBe(expected: AuthType::SanctumActAs)
        ->and($detector->detect(route: $route, middleware: ['sanctum.auth']))->toBe(expected: AuthType::SanctumActAs)
        ->and($detector->detect(route: $route, middleware: ['api_key']))->toBe(expected: AuthType::ApiKey)
        ->and($detector->detect(route: $route, middleware: ['auth:api']))->toBe(expected: AuthType::Bearer)
        ->and($detector->detect(route: $route, middleware: ['bearer']))->toBe(expected: AuthType::Bearer)
        ->and($detector->detect(route: $route, middleware: ['auth:basic']))->toBe(expected: AuthType::Basic)
        ->and($detector->detect(route: $route, middleware: ['auth']))->toBe(expected: AuthType::Session)
        ->and($detector->detect(route: $route, middleware: []))->toBe(expected: AuthType::None);
});

it('detects route version from URI patterns', function (): void {
    $detector = new RouteVersionDetector;
    $route = new Route(methods: ['GET'], uri: 'api/v1/users', action: ['uses' => static fn (): string => 'ok']);

    expect($detector->detect(route: $route, uri: 'api/v1/users', middleware: []))->toBe(expected: 'v1')
        ->and($detector->detect(route: $route, uri: 'api/v2.1/users', middleware: []))->toBe(expected: 'v2.1')
        ->and($detector->detect(route: $route, uri: 'api/2026-10-01/users', middleware: []))->toBe(expected: '2026-10-01')
        ->and($detector->detect(route: $route, uri: 'api/users', middleware: []))->toBeNull();
});

it('detects route version from middleware', function (): void {
    $detector = new RouteVersionDetector;
    $route = new Route(methods: ['GET'], uri: 'api/users', action: ['uses' => static fn (): string => 'ok']);

    expect($detector->detect(route: $route, uri: 'api/users', middleware: ['version:v2']))->toBe(expected: 'v2')
        ->and($detector->detect(route: $route, uri: 'api/users', middleware: ['api-version:3']))->toBe(expected: 'v3');
});
