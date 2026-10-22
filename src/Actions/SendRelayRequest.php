<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Actions;

use Illuminate\Contracts\Events\Dispatcher;
use Isaacjuwon\Probe\Auth\AuthApplierFactory;
use Isaacjuwon\Probe\Contracts\RelayTransport;
use Isaacjuwon\Probe\Data\RelayRequest;
use Isaacjuwon\Probe\Data\RelayResult;
use Isaacjuwon\Probe\Events\RequestRelayed;

/**
 * Action: Applies authentication, dispatches relay request through decorated transport, and fires lifecycle event.
 */
final class SendRelayRequest
{
    public function __construct(
        private readonly RelayTransport $transport,
        private readonly AuthApplierFactory $authFactory,
        private readonly ?Dispatcher $events = null,
    ) {}

    public function __invoke(RelayRequest $request): RelayResult
    {
        $authenticatedRequest = $this->authFactory->apply(request: $request);

        $result = $this->transport->send(request: $authenticatedRequest);

        if ($this->events !== null) {
            $this->events->dispatch(
                event: new RequestRelayed(
                    request: $authenticatedRequest,
                    result: $result,
                ),
            );
        }

        return $result;
    }
}
