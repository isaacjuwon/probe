<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Collectors;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Isaacjuwon\Probe\Contracts\Collector;

final class QueryCollector implements Collector
{
    private bool $active = false;

    /**
     * @var list<array<string, mixed>>
     */
    private array $queries = [];

    private bool $listening = false;

    public function name(): string
    {
        return 'queries';
    }

    public function start(): void
    {
        $this->queries = [];
        $this->active = true;

        if (! $this->listening) {
            $this->listening = true;

            DB::listen(callback: function (QueryExecuted $query): void {
                if (! $this->active) {
                    return;
                }

                $this->queries[] = [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time,
                    'connection' => $query->connectionName,
                ];
            });
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function stop(): array
    {
        $this->active = false;
        $captured = $this->queries;
        $this->queries = [];

        return $captured;
    }
}
