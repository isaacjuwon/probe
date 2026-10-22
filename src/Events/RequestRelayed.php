<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Events;

use Isaacjuwon\Probe\Data\RelayRequest;
use Isaacjuwon\Probe\Data\RelayResult;

final class RequestRelayed
{
    public function __construct(
        public readonly RelayRequest $request,
        public readonly RelayResult $result,
    ) {}
}
