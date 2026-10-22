<?php

declare(strict_types=1);

use Isaacjuwon\Probe\Data\FieldSchema;
use Isaacjuwon\Probe\Enums\FieldType;
use Isaacjuwon\Probe\Schema\FakerExampleGenerator;
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
use Isaacjuwon\Probe\Schema\RuleSchemaBuilder;

it('maps required and nullable rules', function (): void {
    $requiredMapper = new RequiredMapper;
    $nullableMapper = new NullableMapper;

    $schema = new FieldSchema(name: 'title');

    $schema = $requiredMapper->map(
        field: 'title',
        rule: 'required',
        parameters: [],
        current: $schema,
    );
    expect($schema->required)->toBeTrue();

    $schema = $nullableMapper->map(
        field: 'title',
        rule: 'nullable',
        parameters: [],
        current: $schema,
    );
    expect($schema->nullable)->toBeTrue();
});

it('maps email and min-max rules', function (): void {
    $emailMapper = new EmailMapper;
    $minMaxMapper = new MinMaxMapper;

    $schema = new FieldSchema(name: 'email');
    $schema = $emailMapper->map(field: 'email', rule: 'email', parameters: [], current: $schema);

    expect($schema->type)->toBe(FieldType::String)
        ->and($schema->constraints['format'])->toBe('email');

    $schema = $minMaxMapper->map(field: 'email', rule: 'min', parameters: ['5'], current: $schema);
    $schema = $minMaxMapper->map(field: 'email', rule: 'max', parameters: ['255'], current: $schema);

    expect($schema->constraints['min'])->toBe(5.0)
        ->and($schema->constraints['max'])->toBe(255.0);
});

it('maps in and array rules', function (): void {
    $inMapper = new InMapper;
    $arrayMapper = new ArrayMapper;

    $schema = new FieldSchema(name: 'role');
    $schema = $inMapper->map(field: 'role', rule: 'in', parameters: ['admin', 'user'], current: $schema);

    expect($schema->constraints['allowed'])->toBe(['admin', 'user'])
        ->and($schema->example)->toBe('admin');

    $schema = new FieldSchema(name: 'tags');
    $schema = $arrayMapper->map(field: 'tags', rule: 'array', parameters: [], current: $schema);

    expect($schema->type)->toBe(FieldType::Array)
        ->and($schema->example)->toBe([]);
});

it('maps string numeric boolean and date types', function (): void {
    $stringMapper = new StringTypeMapper;
    $numericMapper = new NumericTypeMapper;
    $booleanMapper = new BooleanTypeMapper;
    $dateMapper = new DateTypeMapper;

    $schema = $stringMapper->map(field: 'name', rule: 'string', parameters: [], current: new FieldSchema(name: 'name'));
    expect($schema->type)->toBe(FieldType::String);

    $schema = $numericMapper->map(field: 'age', rule: 'integer', parameters: [], current: new FieldSchema(name: 'age'));
    expect($schema->type)->toBe(FieldType::Integer);

    $schema = $booleanMapper->map(field: 'is_active', rule: 'boolean', parameters: [], current: new FieldSchema(name: 'is_active'));
    expect($schema->type)->toBe(FieldType::Boolean);

    $schema = $dateMapper->map(field: 'published_at', rule: 'date', parameters: [], current: new FieldSchema(name: 'published_at'));
    expect($schema->constraints['format'])->toBe('date');
});

it('builds comprehensive field schema via rule schema builder', function (): void {
    $builder = new RuleSchemaBuilder;

    $schema = $builder->build(
        field: 'email_address',
        rules: 'required|email|min:5|max:100',
    );

    expect($schema->name)->toBe('email_address')
        ->and($schema->required)->toBeTrue()
        ->and($schema->type)->toBe(FieldType::String)
        ->and($schema->constraints['format'])->toBe('email')
        ->and($schema->constraints['min'])->toBe(5.0)
        ->and($schema->constraints['max'])->toBe(100.0)
        ->and($schema->example)->toBe('user@example.com');
});

it('generates fallback examples with faker example generator', function (): void {
    $generator = new FakerExampleGenerator;

    expect($generator->generate(schema: new FieldSchema(name: 'user_email', type: FieldType::String)))->toBe('user@example.com')
        ->and($generator->generate(schema: new FieldSchema(name: 'password', type: FieldType::String)))->toBe('secret123')
        ->and($generator->generate(schema: new FieldSchema(name: 'is_admin', type: FieldType::Boolean)))->toBeTrue()
        ->and($generator->generate(schema: new FieldSchema(name: 'count', type: FieldType::Integer)))->toBe(1);
});
