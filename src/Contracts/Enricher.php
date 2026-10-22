<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Contracts;

use Isaacjuwon\Probe\Data\Endpoint;

interface Enricher
{
    public function enrich(Endpoint $endpoint): Endpoint;
}
