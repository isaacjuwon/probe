<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Isaacjuwon\Probe\Contracts\RelayTransport;
use Isaacjuwon\Probe\Data\RelayRequest;
use Isaacjuwon\Probe\Enums\HttpMethod;
use Isaacjuwon\Probe\Relay\Exceptions\HostNotAllowedException;
use Isaacjuwon\Probe\Relay\HostGuard;
use Isaacjuwon\Probe\Relay\HttpTransport;

beforeEach(function (): void {
    Gate::define(ability: 'viewProbe', callback: static fn (mixed $user = null): bool => true);
});

it('relays http requests and returns relay result with timing', function (): void {
    Http::fake([
        'http://localhost/api/test' => Http::response(
            body: ['success' => true, 'message' => 'hello'],
            status: 200,
            headers: ['Content-Type' => 'application/json'],
        ),
    ]);

    /** @var RelayTransport $transport */
    $transport = app(abstract: RelayTransport::class);

    $request = new RelayRequest(
        method: HttpMethod::Get,
        url: 'http://localhost/api/test',
        headers: ['Accept' => 'application/json'],
        timeoutSeconds: 10,
    );

    $result = $transport->send(request: $request);

    expect($result->status)->toBe(200)
        ->and($result->body)->toBe(['success' => true, 'message' => 'hello'])
        ->and($result->durationMs)->toBeGreaterThan(0.0);
});

it('blocks disallowed hosts when hostguard is enabled', function (): void {
    $guard = new HostGuard(
        allowedHosts: ['localhost'],
        appUrl: 'http://localhost',
    );

    expect($guard->isAllowed(url: 'http://localhost/api/test'))->toBeTrue()
        ->and($guard->isAllowed(url: 'http://malicious-external-site.com/steal'))->toBeFalse();

    $transport = new HttpTransport(hostGuard: $guard);

    $request = new RelayRequest(
        method: HttpMethod::Get,
        url: 'http://malicious-external-site.com/steal',
    );

    expect(fn () => $transport->send(request: $request))
        ->toThrow(exception: HostNotAllowedException::class);
});

it('relays through the probe relay http controller endpoint', function (): void {
    Http::fake([
        'http://localhost/api/users' => Http::response(
            body: ['users' => []],
            status: 200,
        ),
    ]);

    $response = $this->postJson(
        uri: '/probe/relay',
        data: [
            'method' => 'GET',
            'url' => 'http://localhost/api/users',
            'headers' => ['Accept' => 'application/json'],
            'auth_type' => 'bearer',
            'auth_payload' => ['token' => 'test-token-123'],
        ],
    );

    $response->assertOk()
        ->assertJsonPath(path: 'data.status', expect: 200)
        ->assertJsonPath(path: 'data.body.users', expect: []);
});
