<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Relay;

use Isaacjuwon\Probe\Collectors\CollectorRegistry;
use Isaacjuwon\Probe\Contracts\RelayTransport;
use Isaacjuwon\Probe\Data\RelayRequest;
use Isaacjuwon\Probe\Data\RelayResult;

/**
 * Decorator: captures queries, dumps, logs, and exceptions around the inner transport.
 */
final class QueryCapturingTransport implements RelayTransport
{
    public function __construct(
        private readonly RelayTransport $transport,
        private readonly CollectorRegistry $registry,
    ) {}

    public function send(RelayRequest $request): RelayResult
    {
        $this->registry->startAll();

        try {
            $result = $this->transport->send(request: $request);
        } finally {
            $collected = $this->registry->stopAll();
        }

        $queries = array_merge($result->queries, $collected['queries'] ?? []);
        $dumps = array_merge($result->dumps, $collected['dumps'] ?? []);
        $logs = array_merge($result->logs, $collected['logs'] ?? []);
        $exceptions = array_merge($result->exceptions, $collected['exceptions'] ?? []);

        return new RelayResult(
            status: $result->status,
            headers: $result->headers,
            body: $result->body,
            durationMs: $result->durationMs,
            queries: $queries,
            dumps: $dumps,
            logs: $logs,
            exceptions: $exceptions,
            dryRun: $result->dryRun,
            cookies: $result->cookies,
        );
    }
}
