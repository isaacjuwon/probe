<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Importers;

use Illuminate\Support\Collection;
use Isaacjuwon\Probe\Data\Endpoint;
use Isaacjuwon\Probe\Enums\AuthType;
use Isaacjuwon\Probe\Enums\HttpMethod;

/**
 * Adapter: Normalizes Postman Collections (v2.1) to Probe Endpoints.
 */
final class PostmanImporter
{
    /**
     * @param  array<string, mixed>|string  $collection
     * @return Collection<int, Endpoint>
     */
    public function import(array|string $collection): Collection
    {
        if (is_string(value: $collection)) {
            $decoded = json_decode(json: $collection, associative: true);

            if (! is_array(value: $decoded)) {
                return new Collection;
            }

            $collection = $decoded;
        }

        $items = $collection['item'] ?? [];

        if (! is_array(value: $items)) {
            return new Collection;
        }

        $endpoints = [];
        $this->parseItems(items: $items, endpoints: $endpoints);

        return new Collection(items: $endpoints);
    }

    /**
     * @param  array<mixed, mixed>  $items
     * @param  list<Endpoint>  $endpoints
     */
    private function parseItems(array $items, array &$endpoints, ?string $group = null): void
    {
        foreach ($items as $item) {
            if (! is_array(value: $item)) {
                continue;
            }

            if (isset($item['item']) && is_array(value: $item['item'])) {
                $subGroup = (string) ($item['name'] ?? 'Folder');
                $this->parseItems(items: $item['item'], endpoints: $endpoints, group: $subGroup);

                continue;
            }

            if (! isset($item['request']) || ! is_array(value: $item['request'])) {
                continue;
            }

            $request = $item['request'];
            $methodStr = (string) ($request['method'] ?? 'GET');
            $method = HttpMethod::tryFrom(value: strtoupper(string: $methodStr));

            if ($method === null) {
                continue;
            }

            $rawUrl = is_array(value: $request['url'])
                ? ($request['url']['raw'] ?? '')
                : (string) ($request['url'] ?? '');

            $uri = parse_url(url: (string) $rawUrl, component: PHP_URL_PATH) ?? (string) $rawUrl;

            $version = preg_match(pattern: '/(?:\/|^)(v\d+(?:\.\d+)?(?:-[a-z0-9]+)?)(?:\/|$)/i', subject: (string) $uri, matches: $vMatches)
                ? strtolower(string: $vMatches[1])
                : null;

            $endpoints[] = new Endpoint(
                method: $method,
                uri: (string) $uri,
                name: isset($item['name']) ? (string) $item['name'] : null,
                group: $group,
                description: isset($request['description']) ? (string) $request['description'] : null,
                auth: isset($request['auth']) ? AuthType::Bearer : AuthType::None,
                version: $version,
            );
        }
    }
}
