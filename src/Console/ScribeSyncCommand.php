<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Console;

use Illuminate\Console\Command;
use Isaacjuwon\Probe\Discovery\ScribeSource;

final class ScribeSyncCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'probe:scribe-sync {--path= : Custom Scribe directory path}';

    /**
     * @var string
     */
    protected $description = 'Discover and synchronize Scribe documentation endpoints';

    public function handle(): int
    {
        $customPath = $this->option(key: 'path');
        $path = is_string(value: $customPath) ? $customPath : null;

        $source = new ScribeSource(basePath: $path);
        $endpoints = $source->endpoints();

        if ($endpoints->isEmpty()) {
            $this->warn(string: 'No Scribe endpoints found or Scribe documentation is not generated.');

            return self::SUCCESS;
        }

        $this->info(string: sprintf('Synchronized %d Scribe endpoints for Probe.', $endpoints->count()));

        return self::SUCCESS;
    }
}
