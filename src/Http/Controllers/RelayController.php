<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Http\Controllers;

use Illuminate\Support\Facades\Gate;
use Isaacjuwon\Probe\Actions\SendRelayRequest;
use Isaacjuwon\Probe\Http\Requests\RelayFormRequest;
use Isaacjuwon\Probe\Http\Resources\RelayResultResource;
use Isaacjuwon\Probe\Relay\RelayRequestBuilder;

final class RelayController
{
    public function __construct(
        private readonly SendRelayRequest $sendRelayRequest,
        private readonly RelayRequestBuilder $builder = new RelayRequestBuilder,
    ) {}

    public function __invoke(RelayFormRequest $request): RelayResultResource
    {
        Gate::authorize(ability: 'viewProbe');

        $relayRequest = $this->builder
            ->fromArray(payload: $request->validated())
            ->build();

        $result = ($this->sendRelayRequest)(request: $relayRequest);

        return new RelayResultResource(resource: $result);
    }
}
