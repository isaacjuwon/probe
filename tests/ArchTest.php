<?php

declare(strict_types=1);

arch()->preset()->php();

arch()->preset()->security();

arch('it will not use dd(), ddd(), env(), or exit() in src')
    ->expect(['dd', 'ddd', 'env', 'exit'])
    ->not->toBeUsed();

arch('the package source declares strict types')
    ->expect('Isaacjuwon\Probe')
    ->toUseStrictTypes();

arch('classes in src are final')
    ->expect('Isaacjuwon\Probe')
    ->classes()
    ->toBeFinal();

arch('contracts do not depend on Illuminate classes beyond Collection')
    ->expect('Isaacjuwon\Probe\Contracts')
    ->toOnlyUse([
        'Isaacjuwon\Probe',
        'Illuminate\Support\Collection',
    ]);
