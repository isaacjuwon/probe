<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Data;

use Isaacjuwon\Probe\Enums\AuthType;
use Isaacjuwon\Probe\Enums\HttpMethod;

final readonly class Endpoint
{
    /**
     * @param  list<string>  $middleware
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>  $parameters
     * @param  array<string, FieldSchema>  $fields
     */
    public function __construct(
        public HttpMethod $method,
        public string $uri,
        public ?string $name = null,
        public ?string $action = null,
        public array $middleware = [],
        public array $headers = [],
        public array $parameters = [],
        public array $fields = [],
        public ?string $group = null,
        public ?string $description = null,
        public AuthType $auth = AuthType::None,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'method' => $this->method->value,
            'uri' => $this->uri,
            'name' => $this->name,
            'action' => $this->action,
            'middleware' => $this->middleware,
            'headers' => $this->headers,
            'parameters' => $this->parameters,
            'fields' => array_map(
                callback: static fn (FieldSchema $field): array => $field->toArray(),
                array: $this->fields,
            ),
            'group' => $this->group,
            'description' => $this->description,
            'auth' => $this->auth->value,
        ];
    }
}
