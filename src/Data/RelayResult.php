<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Data;

final readonly class RelayResult
{
    /**
     * @param  array<string, mixed>  $headers
     * @param  list<mixed>  $queries
     * @param  list<mixed>  $dumps
     * @param  list<mixed>  $logs
     * @param  list<mixed>  $exceptions
     * @param  list<mixed>  $cookies
     */
    public function __construct(
        public int $status,
        public array $headers = [],
        public mixed $body = null,
        public float $durationMs = 0.0,
        public array $queries = [],
        public array $dumps = [],
        public array $logs = [],
        public array $exceptions = [],
        public bool $dryRun = false,
        public array $cookies = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'headers' => $this->headers,
            'body' => $this->body,
            'duration_ms' => $this->durationMs,
            'queries' => $this->queries,
            'dumps' => $this->dumps,
            'logs' => $this->logs,
            'exceptions' => $this->exceptions,
            'dry_run' => $this->dryRun,
            'cookies' => $this->cookies,
        ];
    }
}
