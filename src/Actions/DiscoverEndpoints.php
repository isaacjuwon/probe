<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Actions;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Collection;
use Isaacjuwon\Probe\Contracts\EndpointSource;
use Isaacjuwon\Probe\Data\Endpoint;
use Isaacjuwon\Probe\Enrichment\EnrichmentPipeline;
use Isaacjuwon\Probe\Events\EndpointsRefreshed;

/**
 * Action: Discovers and enriches all available API endpoints.
 */
final class DiscoverEndpoints
{
    public function __construct(
        private readonly EndpointSource $source,
        private readonly EnrichmentPipeline $enrichmentPipeline,
        private readonly ?Dispatcher $events = null,
    ) {}

    /**
     * @return Collection<int, Endpoint>
     */
    public function __invoke(): Collection
    {
        $discovered = $this->source->endpoints();
        $enriched = $this->enrichmentPipeline->enrichAll(endpoints: $discovered);

        if ($this->events !== null) {
            $this->events->dispatch(
                event: new EndpointsRefreshed(endpoints: $enriched),
            );
        }

        return $enriched;
    }
}
