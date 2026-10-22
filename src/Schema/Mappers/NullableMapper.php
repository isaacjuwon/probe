<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Schema\Mappers;

use Isaacjuwon\Probe\Contracts\RuleMapper;
use Isaacjuwon\Probe\Data\FieldSchema;

final class NullableMapper implements RuleMapper
{
    public function canHandle(string $rule): bool
    {
        return $rule === 'nullable';
    }

    public function map(string $field, string $rule, array $parameters, FieldSchema $current): FieldSchema
    {
        $rules = $current->rules;
        $rules[] = $rule;

        return new FieldSchema(
            name: $current->name,
            type: $current->type,
            required: $current->required,
            nullable: true,
            rules: $rules,
            constraints: $current->constraints,
            example: $current->example,
            description: $current->description,
        );
    }
}
