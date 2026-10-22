<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use Isaacjuwon\Probe\Contracts\EndpointSource;
use Isaacjuwon\Probe\Contracts\RelayTransport;
use Isaacjuwon\Probe\Contracts\RequestRepository;
use Isaacjuwon\Probe\Probe;

it('merges probe configuration', function (): void {
    expect(config(key: 'probe.path'))->toBe('probe')
        ->and(config(key: 'probe.middleware'))->toBe(['web'])
        ->and(config(key: 'probe.storage.disk'))->toBe('local');
});

it('defines the viewProbe gate', function (): void {
    expect(Gate::has(ability: 'viewProbe'))->toBeTrue();
});

it('resolves probe core services from the container', function (): void {
    expect(app(abstract: RelayTransport::class))->toBeInstanceOf(class: RelayTransport::class)
        ->and(app(abstract: RequestRepository::class))->toBeInstanceOf(class: RequestRepository::class)
        ->and(app(abstract: EndpointSource::class))->toBeInstanceOf(class: EndpointSource::class)
        ->and(app(abstract: Probe::class))->toBeInstanceOf(class: Probe::class);
});

it('registers probe artisan commands', function (): void {
    $this->artisan(command: 'probe:export')
        ->assertSuccessful();

    $this->artisan(command: 'probe:scribe-sync')
        ->assertSuccessful();
});
