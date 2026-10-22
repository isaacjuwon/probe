<?php

declare(strict_types=1);

use Isaacjuwon\Probe\Data\Endpoint;
use Isaacjuwon\Probe\Data\FieldSchema;
use Isaacjuwon\Probe\Enrichment\EnrichmentPipeline;
use Isaacjuwon\Probe\Enrichment\ScribeEnricher;
use Isaacjuwon\Probe\Enums\HttpMethod;
use Isaacjuwon\Probe\Importers\OpenApiImporter;
use Isaacjuwon\Probe\Importers\PostmanImporter;

it('imports endpoints from openapi specification', function (): void {
    $importer = new OpenApiImporter;

    $spec = [
        'paths' => [
            '/api/users' => [
                'get' => [
                    'operationId' => 'listUsers',
                    'summary' => 'List all users',
                    'tags' => ['Users'],
                ],
                'post' => [
                    'operationId' => 'createUser',
                    'summary' => 'Create a user',
                    'tags' => ['Users'],
                    'requestBody' => [
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'required' => ['email'],
                                    'properties' => [
                                        'email' => [
                                            'type' => 'string',
                                            'example' => 'test@example.com',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $endpoints = $importer->import(spec: $spec);

    expect($endpoints->count())->toBe(2);

    $get = $endpoints->first(callback: static fn (Endpoint $e): bool => $e->method === HttpMethod::Get);
    expect($get?->uri)->toBe('/api/users')
        ->and($get?->group)->toBe('Users');

    $post = $endpoints->first(callback: static fn (Endpoint $e): bool => $e->method === HttpMethod::Post);
    expect($post?->fields['email']->required)->toBeTrue()
        ->and($post?->fields['email']->example)->toBe('test@example.com');
});

it('imports endpoints from postman collections', function (): void {
    $importer = new PostmanImporter;

    $collection = [
        'item' => [
            [
                'name' => 'Users Folder',
                'item' => [
                    [
                        'name' => 'Get All Users',
                        'request' => [
                            'method' => 'GET',
                            'url' => [
                                'raw' => 'http://localhost/api/users',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $endpoints = $importer->import(collection: $collection);

    expect($endpoints->count())->toBe(1);

    $endpoint = $endpoints->first();
    expect($endpoint?->method)->toBe(HttpMethod::Get)
        ->and($endpoint?->uri)->toBe('/api/users')
        ->and($endpoint?->group)->toBe('Users Folder');
});

it('enriches endpoints using scribe enricher and enrichment pipeline', function (): void {
    $scribeEndpoint = new Endpoint(
        method: HttpMethod::Post,
        uri: 'api/users',
        fields: [
            'name' => new FieldSchema(
                name: 'name',
                example: 'John Doe',
                description: 'The user full name',
            ),
        ],
        description: 'Create a new user account',
    );

    $enricher = new ScribeEnricher(scribeEndpoints: [$scribeEndpoint]);
    $pipeline = new EnrichmentPipeline(enrichers: [$enricher]);

    $baseEndpoint = new Endpoint(
        method: HttpMethod::Post,
        uri: 'api/users',
        fields: [
            'name' => new FieldSchema(name: 'name', required: true),
        ],
    );

    $enriched = $pipeline->enrich(endpoint: $baseEndpoint);

    expect($enriched->description)->toBe('Create a new user account')
        ->and($enriched->fields['name']->required)->toBeTrue()
        ->and($enriched->fields['name']->example)->toBe('John Doe')
        ->and($enriched->fields['name']->description)->toBe('The user full name');
});
