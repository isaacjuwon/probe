<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Http\Controllers;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Isaacjuwon\Probe\Actions\DiscoverEndpoints;
use Isaacjuwon\Probe\Http\Resources\EndpointResource;

final class EndpointIndexController
{
    public function __construct(
        private readonly DiscoverEndpoints $discoverEndpoints,
    ) {}

    public function __invoke(): AnonymousResourceCollection
    {
        Gate::authorize(ability: 'viewProbe');

        $endpoints = ($this->discoverEndpoints)();

        return EndpointResource::collection(resource: $endpoints);
    }
}
