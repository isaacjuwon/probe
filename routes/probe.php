<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Isaacjuwon\Probe\Http\Controllers\EndpointIndexController;
use Isaacjuwon\Probe\Http\Controllers\ProbeDashboardController;
use Isaacjuwon\Probe\Http\Controllers\ProbeUserIndexController;
use Isaacjuwon\Probe\Http\Controllers\RelayController;
use Isaacjuwon\Probe\Http\Controllers\RequestSaveController;

Route::prefix((string) config(key: 'probe.path', default: 'probe'))
    ->middleware((array) config(key: 'probe.middleware', default: ['web']))
    ->group(function (): void {
        Route::get('/', ProbeDashboardController::class)->name(name: 'probe.index');
        Route::get('/endpoints', EndpointIndexController::class)->name(name: 'probe.endpoints');
        Route::get('/users', ProbeUserIndexController::class)->name(name: 'probe.users');
        Route::post('/relay', RelayController::class)->name(name: 'probe.relay');
        Route::post('/requests', RequestSaveController::class)->name(name: 'probe.requests.save');
    });
