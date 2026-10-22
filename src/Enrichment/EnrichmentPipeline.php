<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Enrichment;

use Illuminate\Support\Collection;
use Isaacjuwon\Probe\Contracts\Enricher;
use Isaacjuwon\Probe\Data\Endpoint;

/**
 * Pipeline: Passes Endpoints through an ordered series of Enricher pipes.
 */
final class EnrichmentPipeline
{
    /**
     * @var list<Enricher>
     */
    private array $enrichers;

    /**
     * @param  list<Enricher>  $enrichers
     */
    public function __construct(array $enrichers = [])
    {
        $this->enrichers = $enrichers;
    }

    public function pipe(Enricher $enricher): self
    {
        $this->enrichers[] = $enricher;

        return $this;
    }

    public function enrich(Endpoint $endpoint): Endpoint
    {
        $current = $endpoint;

        foreach ($this->enrichers as $enricher) {
            $current = $enricher->enrich(endpoint: $current);
        }

        return $current;
    }

    /**
     * @param  Collection<int, Endpoint>  $endpoints
     * @return Collection<int, Endpoint>
     */
    public function enrichAll(Collection $endpoints): Collection
    {
        return $endpoints->map(
            callback: fn (Endpoint $endpoint): Endpoint => $this->enrich(endpoint: $endpoint),
        );
    }
}
