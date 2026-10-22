<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Collectors;

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Isaacjuwon\Probe\Contracts\Collector;

final class LogCollector implements Collector
{
    private bool $active = false;

    /**
     * @var list<array<string, mixed>>
     */
    private array $logs = [];

    private bool $listening = false;

    public function name(): string
    {
        return 'logs';
    }

    public function start(): void
    {
        $this->logs = [];
        $this->active = true;

        if (! $this->listening) {
            $this->listening = true;

            Event::listen(
                events: MessageLogged::class,
                listener: function (MessageLogged $event): void {
                    if (! $this->active) {
                        return;
                    }

                    $this->logs[] = [
                        'level' => $event->level,
                        'message' => $event->message,
                        'context' => $event->context,
                    ];
                },
            );
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function stop(): array
    {
        $this->active = false;
        $captured = $this->logs;
        $this->logs = [];

        return $captured;
    }
}
