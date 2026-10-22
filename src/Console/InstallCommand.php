<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Console;

use Illuminate\Console\Command;

final class InstallCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'probe:install';

    /**
     * @var string
     */
    protected $description = 'Install and publish all Probe assets and configuration';

    public function handle(): int
    {
        $this->info(string: 'Publishing Probe configuration...');
        $this->call(
            command: 'vendor:publish',
            arguments: [
                '--tag' => 'probe-config',
                '--force' => true,
            ],
        );

        $this->info(string: 'Publishing Probe public assets...');
        $this->call(
            command: 'vendor:publish',
            arguments: [
                '--tag' => 'probe-assets',
                '--force' => true,
            ],
        );

        $this->info(string: 'Probe successfully installed.');

        return self::SUCCESS;
    }
}
