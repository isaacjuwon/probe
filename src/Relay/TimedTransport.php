<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Relay;

use Isaacjuwon\Probe\Contracts\RelayTransport;
use Isaacjuwon\Probe\Data\RelayRequest;
use Isaacjuwon\Probe\Data\RelayResult;

/**
 * Decorator: measures roundtrip execution time of the inner transport.
 */
final class TimedTransport implements RelayTransport
{
    public function __construct(
        private readonly RelayTransport $transport,
    ) {}

    public function send(RelayRequest $request): RelayResult
    {
        $start = microtime(as_float: true);

        $result = $this->transport->send(request: $request);

        $durationMs = (microtime(as_float: true) - $start) * 1000;

        return new RelayResult(
            status: $result->status,
            headers: $result->headers,
            body: $result->body,
            durationMs: round(num: $durationMs, precision: 2),
            queries: $result->queries,
            dumps: $result->dumps,
            logs: $result->logs,
            exceptions: $result->exceptions,
            dryRun: $result->dryRun,
            cookies: $result->cookies,
        );
    }
}
