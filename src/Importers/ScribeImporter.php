<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Importers;

use Illuminate\Support\Collection;
use Isaacjuwon\Probe\Data\Endpoint;
use Isaacjuwon\Probe\Discovery\ScribeSource;

/**
 * Adapter: Normalizes Scribe documentation endpoints to Probe Endpoints.
 */
final class ScribeImporter
{
    public function __construct(
        private readonly ?string $path = null,
    ) {}

    /**
     * @return Collection<int, Endpoint>
     */
    public function import(): Collection
    {
        $source = new ScribeSource(basePath: $this->path);

        return $source->endpoints();
    }
}
