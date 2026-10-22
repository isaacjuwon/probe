<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Auth;

use Isaacjuwon\Probe\Contracts\AuthApplier;
use Isaacjuwon\Probe\Data\RelayRequest;
use Isaacjuwon\Probe\Enums\AuthType;

final class ApiKeyApplier implements AuthApplier
{
    public function canApply(AuthType $type): bool
    {
        return $type === AuthType::ApiKey;
    }

    public function apply(RelayRequest $request): RelayRequest
    {
        $name = (string) ($request->authPayload['name'] ?? 'X-API-Key');
        $value = (string) ($request->authPayload['value'] ?? '');
        $in = (string) ($request->authPayload['in'] ?? 'header');

        if ($value === '') {
            return $request;
        }

        $headers = $request->headers;
        $query = $request->query;

        if ($in === 'query') {
            $query[$name] = $value;
        } else {
            $headers[$name] = $value;
        }

        return $request->with(attributes: [
            'headers' => $headers,
            'query' => $query,
        ]);
    }
}
