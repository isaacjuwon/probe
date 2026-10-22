<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Schema\Mappers;

use Isaacjuwon\Probe\Contracts\RuleMapper;
use Isaacjuwon\Probe\Data\FieldSchema;
use Isaacjuwon\Probe\Enums\FieldType;

final class EmailMapper implements RuleMapper
{
    public function canHandle(string $rule): bool
    {
        return $rule === 'email';
    }

    public function map(string $field, string $rule, array $parameters, FieldSchema $current): FieldSchema
    {
        $rules = $current->rules;
        $rules[] = $rule;

        $constraints = $current->constraints;
        $constraints['format'] = 'email';

        return new FieldSchema(
            name: $current->name,
            type: FieldType::String,
            required: $current->required,
            nullable: $current->nullable,
            rules: $rules,
            constraints: $constraints,
            example: $current->example ?? 'user@example.com',
            description: $current->description,
        );
    }
}
