<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Collectors;

use Isaacjuwon\Probe\Contracts\Collector;
use Throwable;

final class ExceptionCollector implements Collector
{
    /**
     * @var list<array<string, mixed>>
     */
    private array $exceptions = [];

    public function name(): string
    {
        return 'exceptions';
    }

    public function start(): void
    {
        $this->exceptions = [];
    }

    public function record(Throwable $throwable): void
    {
        $this->exceptions[] = [
            'class' => get_class(object: $throwable),
            'message' => $throwable->getMessage(),
            'code' => $throwable->getCode(),
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function stop(): array
    {
        $captured = $this->exceptions;
        $this->exceptions = [];

        return $captured;
    }
}
