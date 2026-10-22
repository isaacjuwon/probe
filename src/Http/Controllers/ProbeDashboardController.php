<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

final class ProbeDashboardController
{
    public function __invoke(): View
    {
        Gate::authorize(ability: 'viewProbe');

        return view(
            view: 'probe::app',
            data: [
                'probeScript' => asset(path: 'vendor/probe/probe.js'),
                'probeStyle' => asset(path: 'vendor/probe/probe.css'),
            ],
        );
    }
}
