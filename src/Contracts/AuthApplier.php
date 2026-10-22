<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Contracts;

use Isaacjuwon\Probe\Data\RelayRequest;
use Isaacjuwon\Probe\Enums\AuthType;

interface AuthApplier
{
    public function canApply(AuthType $type): bool;

    public function apply(RelayRequest $request): RelayRequest;
}
