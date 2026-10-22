<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Isaacjuwon\Probe\Auth\ApiKeyApplier;
use Isaacjuwon\Probe\Auth\AuthApplierFactory;
use Isaacjuwon\Probe\Auth\BasicApplier;
use Isaacjuwon\Probe\Auth\BearerApplier;
use Isaacjuwon\Probe\Auth\SessionApplier;
use Isaacjuwon\Probe\Data\RelayRequest;
use Isaacjuwon\Probe\Enums\AuthType;
use Isaacjuwon\Probe\Enums\HttpMethod;

it('applies bearer token authentication', function (): void {
    $applier = new BearerApplier;
    $request = new RelayRequest(
        method: HttpMethod::Get,
        url: 'http://localhost/api/me',
        authType: AuthType::Bearer,
        authPayload: ['token' => 'my-secret-token'],
    );

    $applied = $applier->apply(request: $request);

    expect($applied->headers['Authorization'])->toBe('Bearer my-secret-token');
});

it('applies basic authentication', function (): void {
    $applier = new BasicApplier;
    $request = new RelayRequest(
        method: HttpMethod::Get,
        url: 'http://localhost/api/me',
        authType: AuthType::Basic,
        authPayload: ['username' => 'admin', 'password' => 'secret'],
    );

    $applied = $applier->apply(request: $request);

    expect($applied->headers['Authorization'])->toBe('Basic '.base64_encode(string: 'admin:secret'));
});

it('applies api key authentication in headers and query parameters', function (): void {
    $applier = new ApiKeyApplier;

    $headerRequest = new RelayRequest(
        method: HttpMethod::Get,
        url: 'http://localhost/api/me',
        authType: AuthType::ApiKey,
        authPayload: ['name' => 'X-API-Key', 'value' => 'key123', 'in' => 'header'],
    );
    $appliedHeader = $applier->apply(request: $headerRequest);
    expect($appliedHeader->headers['X-API-Key'])->toBe('key123');

    $queryRequest = new RelayRequest(
        method: HttpMethod::Get,
        url: 'http://localhost/api/me',
        authType: AuthType::ApiKey,
        authPayload: ['name' => 'api_key', 'value' => 'key123', 'in' => 'query'],
    );
    $appliedQuery = $applier->apply(request: $queryRequest);
    expect($appliedQuery->query['api_key'])->toBe('key123');
});

it('resolves appliers from auth applier factory', function (): void {
    $factory = new AuthApplierFactory;

    expect($factory->forType(type: AuthType::Bearer))->toBeInstanceOf(class: BearerApplier::class)
        ->and($factory->forType(type: AuthType::Basic))->toBeInstanceOf(class: BasicApplier::class)
        ->and($factory->forType(type: AuthType::ApiKey))->toBeInstanceOf(class: ApiKeyApplier::class)
        ->and($factory->forType(type: AuthType::Session))->toBeInstanceOf(class: SessionApplier::class);
});

it('applies session authentication from incoming request cookies', function (): void {
    $currentRequest = Request::create(
        uri: 'http://localhost/probe',
        method: 'GET',
        cookies: ['laravel_session' => 'sess-abc-123', 'XSRF-TOKEN' => 'xsrf-456'],
    );

    $applier = new SessionApplier(currentRequest: $currentRequest);
    $request = new RelayRequest(
        method: HttpMethod::Get,
        url: 'http://localhost/api/me',
        authType: AuthType::Session,
    );

    $applied = $applier->apply(request: $request);

    expect($applied->headers['Cookie'])->toContain('laravel_session=sess-abc-123')
        ->and($applied->headers['Cookie'])->toContain('XSRF-TOKEN=xsrf-456');
});
