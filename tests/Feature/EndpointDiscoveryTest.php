<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Isaacjuwon\Probe\Contracts\EndpointSource;
use Isaacjuwon\Probe\Data\Endpoint;
use Isaacjuwon\Probe\Enums\HttpMethod;

beforeEach(function (): void {
    Gate::define(ability: 'viewProbe', callback: static fn (mixed $user = null): bool => true);

    Route::middleware('api')->prefix('api')->group(function (): void {
        Route::get(uri: '/users', action: static fn (): array => ['data' => []])->name(name: 'users.index');
        Route::post(uri: '/users', action: static fn (): array => ['created' => true])->name(name: 'users.store');
        Route::get(uri: '/posts/{post}', action: static fn (): array => ['post' => 1])->name(name: 'posts.show');
    });
});

it('discovers routes registered in the router', function (): void {
    /** @var EndpointSource $source */
    $source = app(abstract: EndpointSource::class);
    $endpoints = $source->endpoints();

    $uris = $endpoints->map(callback: static fn (Endpoint $e): string => $e->uri)->all();

    expect($uris)->toContain('api/users')
        ->and($uris)->toContain('api/posts/{post}');

    $getUsers = $endpoints->first(callback: static fn (Endpoint $e): bool => $e->uri === 'api/users' && $e->method === HttpMethod::Get);
    expect($getUsers)->not->toBeNull()
        ->and($getUsers?->name)->toBe('users.index');
});

it('serves discovered endpoints via the http endpoint', function (): void {
    $response = $this->getJson(uri: '/probe/endpoints');

    $response->assertOk();

    $data = $response->json();
    expect($data)->toBeArray()
        ->and(count(value: $data))->toBeGreaterThan(0);
});
