<?php

declare(strict_types=1);

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Isaacjuwon\Probe\Contracts\RelayTransport;
use Isaacjuwon\Probe\Data\RelayRequest;
use Isaacjuwon\Probe\Enums\HttpMethod;
use Isaacjuwon\Probe\Relay\Exceptions\HostNotAllowedException;
use Isaacjuwon\Probe\Relay\HostGuard;
use Isaacjuwon\Probe\Relay\HttpTransport;
use Isaacjuwon\Probe\Relay\InProcessTransport;

beforeEach(function (): void {
    Gate::define(ability: 'viewProbe', callback: static fn (mixed $user = null): bool => true);
});

it('relays http requests and returns relay result with timing', function (): void {
    config(key: ['probe.transport' => 'http']);

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
        currentHostResolver: static fn (): string => 'probe-test-app.test',
    );

    expect($guard->isAllowed(url: 'http://localhost/api/test'))->toBeTrue()
        ->and($guard->isAllowed(url: 'http://probe-test-app.test/api/users'))->toBeTrue()
        ->and($guard->isAllowed(url: 'http://myapp.local/api/users'))->toBeTrue()
        ->and($guard->isAllowed(url: 'http://192.168.1.50/api/users'))->toBeTrue()
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
    config(key: ['probe.transport' => 'http']);

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

it('dispatches in-process requests directly via InProcessTransport', function (): void {
    Route::get('/api/internal-test', static fn (): array => ['in_process' => true]);

    /** @var Kernel $kernel */
    $kernel = app(abstract: Kernel::class);
    $transport = new InProcessTransport(kernel: $kernel);

    $request = new RelayRequest(
        method: HttpMethod::Get,
        url: 'http://localhost/api/internal-test',
        headers: ['Accept' => 'application/json'],
    );

    $result = $transport->send(request: $request);

    expect($result->status)->toBe(200)
        ->and($result->body)->toBe(['in_process' => true]);
});

it('supports dry run mode in InProcessTransport', function (): void {
    Route::post('/api/dry-test', static fn (): array => ['saved' => true]);

    /** @var Kernel $kernel */
    $kernel = app(abstract: Kernel::class);
    $transport = new InProcessTransport(kernel: $kernel);

    $request = new RelayRequest(
        method: HttpMethod::Post,
        url: 'http://localhost/api/dry-test',
        headers: ['Accept' => 'application/json'],
        dryRun: true,
    );

    $result = $transport->send(request: $request);

    expect($result->status)->toBe(200)
        ->and($result->dryRun)->toBeTrue();
});

it('returns list of users from probe users endpoint', function (): void {
    $response = $this->getJson(uri: '/probe/users');

    $response->assertOk()
        ->assertJsonStructure(['data']);
});
