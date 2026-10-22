<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Contracts;

use Illuminate\Support\Collection;
use Isaacjuwon\Probe\Data\Endpoint;

interface EndpointSource
{
    /**
     * @return Collection<int, Endpoint>
     */
    public function endpoints(): Collection;
}
