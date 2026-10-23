<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Enrichment;

use Illuminate\Support\Collection;
use Isaacjuwon\Probe\Contracts\Enricher;
use Isaacjuwon\Probe\Data\Endpoint;
use Isaacjuwon\Probe\Data\FieldSchema;

final class ScribeEnricher implements Enricher
{
    /**
     * @var array<string, Endpoint>
     */
    private array $scribeEndpoints = [];

    /**
     * @param  Collection<int, Endpoint>|list<Endpoint>  $scribeEndpoints
     */
    public function __construct(Collection|array $scribeEndpoints = [])
    {
        foreach ($scribeEndpoints as $endpoint) {
            $key = $endpoint->method->value.' '.$endpoint->uri;
            $this->scribeEndpoints[$key] = $endpoint;
        }
    }

    public function enrich(Endpoint $endpoint): Endpoint
    {
        $key = $endpoint->method->value.' '.$endpoint->uri;

        if (! isset($this->scribeEndpoints[$key])) {
            return $endpoint;
        }

        $scribe = $this->scribeEndpoints[$key];

        $fields = $endpoint->fields;

        foreach ($scribe->fields as $fieldName => $scribeField) {
            if (isset($fields[$fieldName])) {
                $current = $fields[$fieldName];
                $fields[$fieldName] = new FieldSchema(
                    name: $current->name,
                    type: $current->type,
                    required: $current->required,
                    nullable: $current->nullable,
                    rules: $current->rules,
                    constraints: $current->constraints,
                    example: $scribeField->example ?? $current->example,
                    description: $scribeField->description ?? $current->description,
                );
            } else {
                $fields[$fieldName] = $scribeField;
            }
        }

        return new Endpoint(
            method: $endpoint->method,
            uri: $endpoint->uri,
            name: $endpoint->name ?? $scribe->name,
            action: $endpoint->action,
            middleware: $endpoint->middleware,
            headers: $endpoint->headers,
            parameters: $endpoint->parameters,
            fields: $fields,
            group: $endpoint->group ?? $scribe->group,
            description: $scribe->description ?? $endpoint->description,
            auth: $endpoint->auth,
            version: $endpoint->version ?? $scribe->version,
        );
    }
}
