<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe;

use Illuminate\Support\Collection;
use Isaacjuwon\Probe\Actions\DiscoverEndpoints;
use Isaacjuwon\Probe\Actions\SaveRequest;
use Isaacjuwon\Probe\Actions\SendRelayRequest;
use Isaacjuwon\Probe\Data\Endpoint;
use Isaacjuwon\Probe\Data\RelayRequest;
use Isaacjuwon\Probe\Data\RelayResult;
use Isaacjuwon\Probe\Relay\RelayRequestBuilder;

final class Probe
{
    public function __construct(
        private readonly DiscoverEndpoints $discoverEndpoints,
        private readonly SendRelayRequest $sendRelayRequest,
        private readonly SaveRequest $saveRequest,
        private readonly RelayRequestBuilder $requestBuilder = new RelayRequestBuilder,
    ) {}

    /**
     * @return Collection<int, Endpoint>
     */
    public function endpoints(): Collection
    {
        return ($this->discoverEndpoints)();
    }

    /**
     * @param  array<string, mixed>|RelayRequest  $request
     */
    public function relay(array|RelayRequest $request): RelayResult
    {
        $relayRequest = $request instanceof RelayRequest
            ? $request
            : $this->requestBuilder->fromArray(payload: $request)->build();

        return ($this->sendRelayRequest)(request: $relayRequest);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(string $id, array $data): bool
    {
        return ($this->saveRequest)(id: $id, data: $data);
    }
}
