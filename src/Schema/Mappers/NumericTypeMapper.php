<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Schema\Mappers;

use Isaacjuwon\Probe\Contracts\RuleMapper;
use Isaacjuwon\Probe\Data\FieldSchema;
use Isaacjuwon\Probe\Enums\FieldType;

final class NumericTypeMapper implements RuleMapper
{
    public function canHandle(string $rule): bool
    {
        return in_array(needle: $rule, haystack: ['integer', 'int', 'numeric', 'decimal'], strict: true);
    }

    public function map(string $field, string $rule, array $parameters, FieldSchema $current): FieldSchema
    {
        $rules = $current->rules;
        $rules[] = $rule;

        $type = in_array(needle: $rule, haystack: ['integer', 'int'], strict: true)
            ? FieldType::Integer
            : FieldType::Number;

        return new FieldSchema(
            name: $current->name,
            type: $type,
            required: $current->required,
            nullable: $current->nullable,
            rules: $rules,
            constraints: $current->constraints,
            example: $current->example ?? ($type === FieldType::Integer ? 1 : 1.0),
            description: $current->description,
        );
    }
}
