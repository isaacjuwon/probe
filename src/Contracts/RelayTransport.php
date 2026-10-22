<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Contracts;

use Isaacjuwon\Probe\Data\RelayRequest;
use Isaacjuwon\Probe\Data\RelayResult;

interface RelayTransport
{
    public function send(RelayRequest $request): RelayResult;
}
