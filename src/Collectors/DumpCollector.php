<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Collectors;

use Isaacjuwon\Probe\Contracts\Collector;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Symfony\Component\VarDumper\VarDumper;

final class DumpCollector implements Collector
{
    /**
     * @var list<string>
     */
    private array $dumps = [];

    /**
     * @var (callable(mixed): mixed)|null
     */
    private mixed $previousHandler = null;

    private bool $active = false;

    public function name(): string
    {
        return 'dumps';
    }

    public function start(): void
    {
        $this->dumps = [];
        $this->active = true;

        $cloner = new VarCloner;
        $dumper = new HtmlDumper;

        $this->previousHandler = VarDumper::setHandler(
            callable: function (mixed $var) use ($cloner, $dumper): void {
                if (! $this->active) {
                    return;
                }

                $output = '';
                $dumper->dump(
                    data: $cloner->cloneVar(var: $var),
                    output: function (int $depth, string $val) use (&$output): void {
                        $output .= $val;
                    },
                );

                $this->dumps[] = $output;
            },
        );
    }

    /**
     * @return list<string>
     */
    public function stop(): array
    {
        $this->active = false;

        VarDumper::setHandler(callable: $this->previousHandler);
        $this->previousHandler = null;

        $captured = $this->dumps;
        $this->dumps = [];

        return $captured;
    }
}
