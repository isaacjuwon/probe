<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Collectors;

use Isaacjuwon\Probe\Contracts\Collector;

final class CollectorRegistry
{
    /**
     * @var array<string, Collector>
     */
    private array $collectors = [];

    /**
     * @param  list<Collector>  $collectors
     */
    public function __construct(array $collectors = [])
    {
        foreach ($collectors as $collector) {
            $this->add(collector: $collector);
        }
    }

    public function add(Collector $collector): self
    {
        $this->collectors[$collector->name()] = $collector;

        return $this;
    }

    public function startAll(): void
    {
        foreach ($this->collectors as $collector) {
            $collector->start();
        }
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function stopAll(): array
    {
        $results = [];

        foreach ($this->collectors as $name => $collector) {
            $results[$name] = $collector->stop();
        }

        return $results;
    }
}
