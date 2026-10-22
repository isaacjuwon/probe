<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Schema\Mappers;

use Isaacjuwon\Probe\Contracts\RuleMapper;
use Isaacjuwon\Probe\Data\FieldSchema;
use Isaacjuwon\Probe\Enums\FieldType;

final class DateTypeMapper implements RuleMapper
{
    public function canHandle(string $rule): bool
    {
        return in_array(needle: $rule, haystack: ['date', 'date_format', 'after', 'before'], strict: true);
    }

    public function map(string $field, string $rule, array $parameters, FieldSchema $current): FieldSchema
    {
        $rules = $current->rules;
        $rules[] = $rule.($parameters !== [] ? ':'.implode(separator: ',', array: $parameters) : '');

        $constraints = $current->constraints;
        $constraints['format'] = 'date';

        return new FieldSchema(
            name: $current->name,
            type: FieldType::String,
            required: $current->required,
            nullable: $current->nullable,
            rules: $rules,
            constraints: $constraints,
            example: $current->example ?? '2026-10-22',
            description: $current->description,
        );
    }
}
