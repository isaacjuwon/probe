<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Tests;

use Isaacjuwon\Probe\ProbeServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            ProbeServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(string: '12345678901234567890123456789012'));
        $app['config']->set('probe.allowed_environments', ['local', 'testing']);
    }
}
