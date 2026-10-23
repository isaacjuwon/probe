<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Importers;

use Illuminate\Support\Collection;
use Isaacjuwon\Probe\Data\Endpoint;
use Isaacjuwon\Probe\Data\FieldSchema;
use Isaacjuwon\Probe\Enums\AuthType;
use Isaacjuwon\Probe\Enums\FieldType;
use Isaacjuwon\Probe\Enums\HttpMethod;

/**
 * Adapter: Normalizes OpenAPI / Swagger 3.x specifications to Probe Endpoints.
 */
final class OpenApiImporter
{
    /**
     * @param  array<string, mixed>|string  $spec
     * @return Collection<int, Endpoint>
     */
    public function import(array|string $spec): Collection
    {
        if (is_string(value: $spec)) {
            $decoded = json_decode(json: $spec, associative: true);

            if (! is_array(value: $decoded)) {
                return new Collection;
            }
            $spec = $decoded;
        }

        $paths = $spec['paths'] ?? [];

        if (! is_array(value: $paths)) {
            return new Collection;
        }

        $endpoints = [];

        foreach ($paths as $uri => $methods) {
            if (! is_array(value: $methods)) {
                continue;
            }

            foreach ($methods as $methodKey => $operation) {
                if (! is_array(value: $operation)) {
                    continue;
                }

                $method = HttpMethod::tryFrom(value: strtoupper(string: (string) $methodKey));

                if ($method === null) {
                    continue;
                }

                $fields = $this->extractFields(operation: $operation);
                $group = isset($operation['tags'][0]) ? (string) $operation['tags'][0] : null;
                $version = preg_match(pattern: '/(?:\/|^)(v\d+(?:\.\d+)?(?:-[a-z0-9]+)?)(?:\/|$)/i', subject: (string) $uri, matches: $vMatches)
                    ? strtolower(string: $vMatches[1])
                    : (isset($spec['info']['version']) ? (string) $spec['info']['version'] : null);

                $endpoints[] = new Endpoint(
                    method: $method,
                    uri: (string) $uri,
                    name: isset($operation['operationId']) ? (string) $operation['operationId'] : null,
                    fields: $fields,
                    group: $group,
                    description: isset($operation['summary']) ? (string) $operation['summary'] : (isset($operation['description']) ? (string) $operation['description'] : null),
                    auth: isset($operation['security']) ? AuthType::Bearer : AuthType::None,
                    version: $version,
                );
            }
        }

        return new Collection(items: $endpoints);
    }

    /**
     * @param  array<string, mixed>  $operation
     * @return array<string, FieldSchema>
     */
    private function extractFields(array $operation): array
    {
        $fields = [];

        $schema = $operation['requestBody']['content']['application/json']['schema']['properties'] ?? null;

        if (is_array(value: $schema)) {
            $requiredList = $operation['requestBody']['content']['application/json']['schema']['required'] ?? [];

            foreach ($schema as $name => $props) {
                if (! is_array(value: $props)) {
                    continue;
                }

                $type = isset($props['type']) ? (string) $props['type'] : 'string';

                $fields[(string) $name] = new FieldSchema(
                    name: (string) $name,
                    type: FieldType::tryFrom(value: $type) ?? FieldType::String,
                    required: in_array(needle: $name, haystack: (array) $requiredList, strict: true),
                    example: $props['example'] ?? null,
                    description: isset($props['description']) ? (string) $props['description'] : null,
                );
            }
        }

        return $fields;
    }
}
