<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Auth;

use Isaacjuwon\Probe\Contracts\AuthApplier;
use Isaacjuwon\Probe\Data\RelayRequest;
use Isaacjuwon\Probe\Enums\AuthType;

final class BasicApplier implements AuthApplier
{
    public function canApply(AuthType $type): bool
    {
        return $type === AuthType::Basic;
    }

    public function apply(RelayRequest $request): RelayRequest
    {
        $username = (string) ($request->authPayload['username'] ?? '');
        $password = (string) ($request->authPayload['password'] ?? '');

        if ($username === '' && $password === '') {
            return $request;
        }

        $credentials = base64_encode(string: $username.':'.$password);
        $headers = $request->headers;
        $headers['Authorization'] = 'Basic '.$credentials;

        return $request->with(attributes: ['headers' => $headers]);
    }
}
