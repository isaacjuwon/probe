<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Auth;

use Isaacjuwon\Probe\Contracts\AuthApplier;
use Isaacjuwon\Probe\Data\RelayRequest;
use Isaacjuwon\Probe\Enums\AuthType;

final class BearerApplier implements AuthApplier
{
    public function canApply(AuthType $type): bool
    {
        return $type === AuthType::Bearer;
    }

    public function apply(RelayRequest $request): RelayRequest
    {
        $token = (string) ($request->authPayload['token'] ?? '');

        if ($token === '') {
            return $request;
        }

        $headers = $request->headers;
        $headers['Authorization'] = 'Bearer '.$token;

        return $request->with(attributes: ['headers' => $headers]);
    }
}
