<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Console;

use Illuminate\Console\Command;
use Isaacjuwon\Probe\Actions\DiscoverEndpoints;
use Isaacjuwon\Probe\Data\Endpoint;

final class ExportCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'probe:export {--format=json : Output format (json)}';

    /**
     * @var string
     */
    protected $description = 'Export all discovered Probe API endpoints';

    public function handle(DiscoverEndpoints $discoverEndpoints): int
    {
        $endpoints = $discoverEndpoints();

        $payload = $endpoints->map(
            callback: static fn (Endpoint $endpoint): array => $endpoint->toArray(),
        )->values()->all();

        $json = json_encode(value: $payload, flags: JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            $this->error(string: 'Failed to encode endpoints.');

            return self::FAILURE;
        }

        $this->line(string: $json);

        return self::SUCCESS;
    }
}
