<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Schema\Mappers;

use Isaacjuwon\Probe\Contracts\RuleMapper;
use Isaacjuwon\Probe\Data\FieldSchema;
use Isaacjuwon\Probe\Enums\FieldType;

final class ArrayMapper implements RuleMapper
{
    public function canHandle(string $rule): bool
    {
        return $rule === 'array';
    }

    public function map(string $field, string $rule, array $parameters, FieldSchema $current): FieldSchema
    {
        $rules = $current->rules;
        $rules[] = $rule;

        return new FieldSchema(
            name: $current->name,
            type: FieldType::Array,
            required: $current->required,
            nullable: $current->nullable,
            rules: $rules,
            constraints: $current->constraints,
            example: $current->example ?? [],
            description: $current->description,
        );
    }
}
