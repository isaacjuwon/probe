<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Auth;

use Isaacjuwon\Probe\Contracts\AuthApplier;
use Isaacjuwon\Probe\Data\RelayRequest;
use Isaacjuwon\Probe\Enums\AuthType;

final class ActAsUserApplier implements AuthApplier
{
    public function canApply(AuthType $type): bool
    {
        return $type === AuthType::SanctumActAs;
    }

    public function apply(RelayRequest $request): RelayRequest
    {
        $userId = $request->authPayload['user_id'] ?? $request->authPayload['userId'] ?? null;

        if ($userId === null) {
            return $request;
        }

        $userModel = config(key: 'auth.providers.users.model');

        if (! is_string(value: $userModel) || ! class_exists(class: $userModel)) {
            return $request;
        }

        /** @var object|null $user */
        $user = $userModel::query()->find(id: $userId);

        if ($user === null || ! method_exists(object_or_class: $user, method: 'createToken')) {
            return $request;
        }

        $tokenResult = $user->createToken('probe-dev-session');
        $plainTextToken = $tokenResult->plainTextToken ?? null;

        if (! is_string(value: $plainTextToken)) {
            return $request;
        }

        $headers = $request->headers;
        $headers['Authorization'] = 'Bearer '.$plainTextToken;

        return $request->with(attributes: ['headers' => $headers]);
    }
}
