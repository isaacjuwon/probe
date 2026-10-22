<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Discovery;

use Illuminate\Support\Collection;
use Isaacjuwon\Probe\Contracts\EndpointSource;
use Isaacjuwon\Probe\Data\Endpoint;
use Isaacjuwon\Probe\Data\FieldSchema;
use Isaacjuwon\Probe\Enums\AuthType;
use Isaacjuwon\Probe\Enums\FieldType;
use Isaacjuwon\Probe\Enums\HttpMethod;

/**
 * Strategy: Discovers endpoints from Scribe documentation files.
 */
final class ScribeSource implements EndpointSource
{
    public function __construct(
        private readonly ?string $basePath = null,
    ) {}

    /**
     * @return Collection<int, Endpoint>
     */
    public function endpoints(): Collection
    {
        $path = $this->basePath ?? base_path(path: '.scribe/endpoints');

        if (! is_dir(filename: $path)) {
            return new Collection;
        }

        $files = glob(pattern: $path.'/*.yaml');

        if ($files === false || $files === []) {
            return new Collection;
        }

        $endpoints = [];

        foreach ($files as $file) {
            $parsed = $this->parseYamlFile(file: $file);

            foreach ($parsed as $item) {
                $endpoint = $this->mapToEndpoint(data: $item);

                if ($endpoint !== null) {
                    $endpoints[] = $endpoint;
                }
            }
        }

        return new Collection(items: $endpoints);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function parseYamlFile(string $file): array
    {
        $content = file_get_contents(filename: $file);

        if ($content === false || trim(string: $content) === '') {
            return [];
        }

        if (function_exists(function: 'yaml_parse')) {
            $parsed = yaml_parse(input: $content);

            if (is_array(value: $parsed) && isset($parsed['endpoints']) && is_array(value: $parsed['endpoints'])) {
                /** @var list<array<string, mixed>> $endpointsList */
                $endpointsList = array_values(array: $parsed['endpoints']);

                return $endpointsList;
            }
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function mapToEndpoint(array $data): ?Endpoint
    {
        $httpMethod = isset($data['httpMethods'][0]) ? (string) $data['httpMethods'][0] : null;

        if ($httpMethod === null) {
            return null;
        }

        $method = HttpMethod::tryFrom(value: strtoupper(string: $httpMethod));

        if ($method === null) {
            return null;
        }

        $uri = (string) ($data['uri'] ?? '');

        if ($uri === '') {
            return null;
        }

        $fields = [];

        if (isset($data['bodyParameters']) && is_array(value: $data['bodyParameters'])) {
            foreach ($data['bodyParameters'] as $name => $param) {
                if (! is_array(value: $param)) {
                    continue;
                }

                $fields[(string) $name] = new FieldSchema(
                    name: (string) $name,
                    type: FieldType::String,
                    required: ! empty($param['required']),
                    example: $param['example'] ?? null,
                    description: isset($param['description']) ? (string) $param['description'] : null,
                );
            }
        }

        return new Endpoint(
            method: $method,
            uri: $uri,
            name: isset($data['title']) ? (string) $data['title'] : null,
            fields: $fields,
            group: isset($data['metadata']['groupName']) ? (string) $data['metadata']['groupName'] : null,
            description: isset($data['description']) ? (string) $data['description'] : null,
            auth: ! empty($data['authenticated']) ? AuthType::Bearer : AuthType::None,
        );
    }
}
