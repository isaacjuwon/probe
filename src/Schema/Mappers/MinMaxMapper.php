<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Schema\Mappers;

use Isaacjuwon\Probe\Contracts\RuleMapper;
use Isaacjuwon\Probe\Data\FieldSchema;

final class MinMaxMapper implements RuleMapper
{
    public function canHandle(string $rule): bool
    {
        return in_array(needle: $rule, haystack: ['min', 'max', 'size', 'between'], strict: true);
    }

    public function map(string $field, string $rule, array $parameters, FieldSchema $current): FieldSchema
    {
        $rules = $current->rules;
        $rules[] = $rule.($parameters !== [] ? ':'.implode(separator: ',', array: $parameters) : '');

        $constraints = $current->constraints;

        if ($rule === 'min' && isset($parameters[0])) {
            $constraints['min'] = is_numeric(value: $parameters[0]) ? (float) $parameters[0] : $parameters[0];
        } elseif ($rule === 'max' && isset($parameters[0])) {
            $constraints['max'] = is_numeric(value: $parameters[0]) ? (float) $parameters[0] : $parameters[0];
        } elseif ($rule === 'size' && isset($parameters[0])) {
            $constraints['size'] = is_numeric(value: $parameters[0]) ? (float) $parameters[0] : $parameters[0];
        } elseif ($rule === 'between' && count(value: $parameters) >= 2) {
            $constraints['min'] = (float) $parameters[0];
            $constraints['max'] = (float) $parameters[1];
        }

        return new FieldSchema(
            name: $current->name,
            type: $current->type,
            required: $current->required,
            nullable: $current->nullable,
            rules: $rules,
            constraints: $constraints,
            example: $current->example,
            description: $current->description,
        );
    }
}
