<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Contracts;

interface Collector
{
    public function name(): string;

    public function start(): void;

    /**
     * @return list<mixed>
     */
    public function stop(): array;
}
