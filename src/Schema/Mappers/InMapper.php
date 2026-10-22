<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Schema\Mappers;

use Isaacjuwon\Probe\Contracts\RuleMapper;
use Isaacjuwon\Probe\Data\FieldSchema;

final class InMapper implements RuleMapper
{
    public function canHandle(string $rule): bool
    {
        return $rule === 'in';
    }

    public function map(string $field, string $rule, array $parameters, FieldSchema $current): FieldSchema
    {
        $rules = $current->rules;
        $rules[] = $rule.($parameters !== [] ? ':'.implode(separator: ',', array: $parameters) : '');

        $constraints = $current->constraints;
        $constraints['allowed'] = $parameters;

        return new FieldSchema(
            name: $current->name,
            type: $current->type,
            required: $current->required,
            nullable: $current->nullable,
            rules: $rules,
            constraints: $constraints,
            example: $parameters[0] ?? $current->example,
            description: $current->description,
        );
    }
}
