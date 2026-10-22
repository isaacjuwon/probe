<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Http\Controllers;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

final class ProbeDashboardController
{
    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function __invoke(): View
    {
        Gate::authorize(ability: 'viewProbe');

        /** @var string $basePath */
        $basePath = config(key: 'probe.path', default: 'probe');
        /** @var string $appName */
        $appName = config(key: 'app.name', default: 'Laravel');
        /** @var string $transport */
        $transport = config(key: 'probe.transport', default: 'http');
        /** @var mixed $scribeEnabled */
        $scribeEnabled = config(key: 'probe.scribe.enabled', default: 'auto');

        return $this->viewFactory->make(
            view: 'probe::app',
            data: [
                'probeScript' => asset(path: 'vendor/probe/probe.js'),
                'probeStyle' => asset(path: 'vendor/probe/probe.css'),
                'bootstrap' => [
                    'basePath' => $basePath,
                    'csrfToken' => csrf_token(),
                    'environment' => app()->environment(),
                    'appName' => $appName,
                    'features' => [
                        'scribe' => $scribeEnabled !== false && $scribeEnabled !== 'false',
                        'inProcessTransport' => $transport === 'in-process',
                    ],
                ],
            ],
        );
    }
}
