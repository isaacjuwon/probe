<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Isaacjuwon\Probe\Actions\SaveRequest;
use Isaacjuwon\Probe\Http\Requests\SaveRequestFormRequest;

final class RequestSaveController
{
    public function __construct(
        private readonly SaveRequest $saveRequest,
    ) {}

    public function __invoke(SaveRequestFormRequest $request): JsonResponse
    {
        Gate::authorize(ability: 'viewProbe');

        $validated = $request->validated();
        $saved = ($this->saveRequest)(
            id: (string) $validated['id'],
            data: (array) $validated['data'],
        );

        return new JsonResponse(
            data: [
                'success' => $saved,
            ],
            status: $saved ? 200 : 500,
        );
    }
}
