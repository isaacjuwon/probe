<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Isaacjuwon\Probe\Data\Endpoint;
use Isaacjuwon\Probe\Data\RelayRequest;
use Isaacjuwon\Probe\Data\RelayResult;
use Isaacjuwon\Probe\Probe as ProbeInstance;

/**
 * @method static Collection<int, Endpoint> endpoints()
 * @method static RelayResult relay(array<string, mixed>|RelayRequest $request)
 * @method static bool save(string $id, array<string, mixed> $data)
 *
 * @see ProbeInstance
 */
final class Probe extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ProbeInstance::class;
    }
}
