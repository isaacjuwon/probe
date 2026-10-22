<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Data;

use Isaacjuwon\Probe\Enums\AuthType;
use Isaacjuwon\Probe\Enums\HttpMethod;

final readonly class RelayRequest
{
    /**
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $authPayload
     */
    public function __construct(
        public HttpMethod $method,
        public string $url,
        public array $headers = [],
        public mixed $body = null,
        public array $query = [],
        public AuthType $authType = AuthType::None,
        public array $authPayload = [],
        public int $timeoutSeconds = 30,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'method' => $this->method->value,
            'url' => $this->url,
            'headers' => $this->headers,
            'body' => $this->body,
            'query' => $this->query,
            'auth_type' => $this->authType->value,
            'auth_payload' => $this->authPayload,
            'timeout_seconds' => $this->timeoutSeconds,
        ];
    }
}
