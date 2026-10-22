<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Relay;

use Isaacjuwon\Probe\Data\RelayRequest;
use Isaacjuwon\Probe\Enums\AuthType;
use Isaacjuwon\Probe\Enums\HttpMethod;

final class RelayRequestBuilder
{
    private HttpMethod $method = HttpMethod::Get;

    private string $url = '';

    /**
     * @var array<string, string>
     */
    private array $headers = [];

    private mixed $body = null;

    /**
     * @var array<string, mixed>
     */
    private array $query = [];

    private AuthType $authType = AuthType::None;

    /**
     * @var array<string, mixed>
     */
    private array $authPayload = [];

    private int $timeoutSeconds = 30;

    private bool $dryRun = false;

    public function setMethod(HttpMethod|string $method): self
    {
        $this->method = is_string(value: $method)
            ? HttpMethod::from(value: strtoupper(string: $method))
            : $method;

        return $this;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @param  array<string, string>  $headers
     */
    public function setHeaders(array $headers): self
    {
        $this->headers = $headers;

        return $this;
    }

    public function setBody(mixed $body): self
    {
        $this->body = $body;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function setQuery(array $query): self
    {
        $this->query = $query;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function setAuth(AuthType|string $type, array $payload = []): self
    {
        $this->authType = is_string(value: $type)
            ? AuthType::from(value: $type)
            : $type;

        $this->authPayload = $payload;

        return $this;
    }

    public function setTimeout(int $seconds): self
    {
        $this->timeoutSeconds = $seconds;

        return $this;
    }

    public function setDryRun(bool $dryRun): self
    {
        $this->dryRun = $dryRun;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function fromArray(array $payload): self
    {
        if (isset($payload['method'])) {
            $this->setMethod(method: (string) $payload['method']);
        }

        if (isset($payload['url'])) {
            $this->setUrl(url: (string) $payload['url']);
        }

        if (isset($payload['headers']) && is_array(value: $payload['headers'])) {
            $this->setHeaders(headers: $payload['headers']);
        }

        if (array_key_exists(key: 'body', array: $payload)) {
            $this->setBody(body: $payload['body']);
        }

        if (isset($payload['query']) && is_array(value: $payload['query'])) {
            $this->setQuery(query: $payload['query']);
        }

        if (isset($payload['auth_type'])) {
            $this->setAuth(
                type: (string) $payload['auth_type'],
                payload: isset($payload['auth_payload']) && is_array(value: $payload['auth_payload'])
                    ? $payload['auth_payload']
                    : [],
            );
        }

        if (isset($payload['timeout_seconds'])) {
            $this->setTimeout(seconds: (int) $payload['timeout_seconds']);
        }

        if (isset($payload['dry_run'])) {
            $this->setDryRun(dryRun: (bool) $payload['dry_run']);
        }

        return $this;
    }

    public function build(): RelayRequest
    {
        return new RelayRequest(
            method: $this->method,
            url: $this->url,
            headers: $this->headers,
            body: $this->body,
            query: $this->query,
            authType: $this->authType,
            authPayload: $this->authPayload,
            timeoutSeconds: $this->timeoutSeconds,
            dryRun: $this->dryRun,
        );
    }
}
