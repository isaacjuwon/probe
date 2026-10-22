<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Events;

use Illuminate\Support\Collection;
use Isaacjuwon\Probe\Data\Endpoint;

final class EndpointsRefreshed
{
    /**
     * @param  Collection<int, Endpoint>  $endpoints
     */
    public function __construct(
        public readonly Collection $endpoints,
    ) {}
}
