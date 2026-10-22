<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Auth;

use Illuminate\Http\Request;
use Isaacjuwon\Probe\Contracts\AuthApplier;
use Isaacjuwon\Probe\Data\RelayRequest;
use Isaacjuwon\Probe\Enums\AuthType;
use Throwable;

final class SessionApplier implements AuthApplier
{
    public function __construct(
        private readonly ?Request $currentRequest = null,
    ) {}

    public function canApply(AuthType $type): bool
    {
        return $type === AuthType::Session;
    }

    public function apply(RelayRequest $request): RelayRequest
    {
        if ($this->currentRequest === null) {
            return $request;
        }

        $headers = $request->headers;
        $cookieParts = [];

        foreach ($this->currentRequest->cookies->all() as $name => $val) {
            if (is_scalar(value: $val)) {
                $cookieParts[] = $name.'='.$val;
            }
        }

        if ($cookieParts !== []) {
            $headers['Cookie'] = implode(separator: '; ', array: $cookieParts);
        }

        try {
            if ($this->currentRequest->hasSession()) {
                $token = $this->currentRequest->session()->token();

                if ($token !== '') {
                    $headers['X-CSRF-TOKEN'] = $token;
                    $headers['X-XSRF-TOKEN'] = $token;
                }
            }
        } catch (Throwable) {
            // Session not initialized or unavailable
        }

        return $request->with(attributes: ['headers' => $headers]);
    }
}
