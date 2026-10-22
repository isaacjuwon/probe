<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Discovery;

use Illuminate\Support\Collection;
use Isaacjuwon\Probe\Contracts\EndpointSource;
use Isaacjuwon\Probe\Data\Endpoint;

/**
 * Null Object: fallback when Scribe is disabled or not installed.
 */
final class NullScribeSource implements EndpointSource
{
    /**
     * @return Collection<int, Endpoint>
     */
    public function endpoints(): Collection
    {
        return new Collection;
    }
}
