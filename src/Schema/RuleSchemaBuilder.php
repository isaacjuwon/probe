<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Schema;

use Isaacjuwon\Probe\Contracts\ExampleGeneratorContract;
use Isaacjuwon\Probe\Contracts\RuleMapper;
use Isaacjuwon\Probe\Data\FieldSchema;
use Isaacjuwon\Probe\Enums\FieldType;
use Isaacjuwon\Probe\Schema\Mappers\ArrayMapper;
use Isaacjuwon\Probe\Schema\Mappers\BooleanTypeMapper;
use Isaacjuwon\Probe\Schema\Mappers\DateTypeMapper;
use Isaacjuwon\Probe\Schema\Mappers\EmailMapper;
use Isaacjuwon\Probe\Schema\Mappers\InMapper;
use Isaacjuwon\Probe\Schema\Mappers\MinMaxMapper;
use Isaacjuwon\Probe\Schema\Mappers\NullableMapper;
use Isaacjuwon\Probe\Schema\Mappers\NumericTypeMapper;
use Isaacjuwon\Probe\Schema\Mappers\RequiredMapper;
use Isaacjuwon\Probe\Schema\Mappers\StringTypeMapper;

final class RuleSchemaBuilder
{
    /**
     * @var list<RuleMapper>
     */
    private array $mappers;

    private ExampleGeneratorContract $exampleGenerator;

    /**
     * @param  list<RuleMapper>  $mappers
     */
    public function __construct(
        array $mappers = [],
        ?ExampleGeneratorContract $exampleGenerator = null,
    ) {
        $this->mappers = $mappers !== [] ? $mappers : [
            new RequiredMapper,
            new NullableMapper,
            new StringTypeMapper,
            new NumericTypeMapper,
            new BooleanTypeMapper,
            new ArrayMapper,
            new EmailMapper,
            new MinMaxMapper,
            new InMapper,
            new DateTypeMapper,
        ];

        $this->exampleGenerator = $exampleGenerator ?? new FakerExampleGenerator;
    }

    public function build(string $field, mixed $rules): FieldSchema
    {
        $normalizedRules = $this->normalizeRules(rules: $rules);
        $schema = new FieldSchema(
            name: $field,
            type: FieldType::String,
        );

        foreach ($normalizedRules as $ruleString) {
            [$ruleName, $parameters] = $this->parseRule(rule: $ruleString);

            foreach ($this->mappers as $mapper) {
                if ($mapper->canHandle(rule: $ruleName)) {
                    $schema = $mapper->map(
                        field: $field,
                        rule: $ruleName,
                        parameters: $parameters,
                        current: $schema,
                    );
                }
            }
        }

        if ($schema->example === null) {
            $example = $this->exampleGenerator->generate(schema: $schema);

            $schema = new FieldSchema(
                name: $schema->name,
                type: $schema->type,
                required: $schema->required,
                nullable: $schema->nullable,
                rules: $schema->rules,
                constraints: $schema->constraints,
                example: $example,
                description: $schema->description,
            );
        }

        return $schema;
    }

    /**
     * @return list<string>
     */
    private function normalizeRules(mixed $rules): array
    {
        if (is_string(value: $rules)) {
            return explode(separator: '|', string: $rules);
        }

        if (! is_array(value: $rules)) {
            return [];
        }

        $result = [];

        foreach ($rules as $rule) {
            if (is_string(value: $rule)) {
                $subRules = explode(separator: '|', string: $rule);
                foreach ($subRules as $sub) {
                    $result[] = $sub;
                }
            } elseif (is_object(value: $rule)) {
                $className = class_basename(class: $rule);
                $result[] = strtolower(string: $className);
            }
        }

        return $result;
    }

    /**
     * @return array{0: string, 1: list<string>}
     */
    private function parseRule(string $rule): array
    {
        if (! str_contains(haystack: $rule, needle: ':')) {
            return [strtolower(string: trim(string: $rule)), []];
        }

        [$name, $params] = explode(separator: ':', string: $rule, limit: 2);

        $parameters = array_map(
            callback: trim(...),
            array: explode(separator: ',', string: $params),
        );

        return [strtolower(string: trim(string: $name)), $parameters];
    }
}
