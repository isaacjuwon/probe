<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Schema;

use Isaacjuwon\Probe\Contracts\ExampleGeneratorContract;
use Isaacjuwon\Probe\Data\FieldSchema;
use Isaacjuwon\Probe\Enums\FieldType;

final class FakerExampleGenerator implements ExampleGeneratorContract
{
    public function generate(FieldSchema $schema): mixed
    {
        if ($schema->example !== null) {
            return $schema->example;
        }

        $lowerName = strtolower(string: $schema->name);

        if (str_contains(haystack: $lowerName, needle: 'email')) {
            return 'user@example.com';
        }

        if (str_contains(haystack: $lowerName, needle: 'password')) {
            return 'secret123';
        }

        if (str_contains(haystack: $lowerName, needle: 'uuid')) {
            return '9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d';
        }

        if (str_ends_with(haystack: $lowerName, needle: '_id') || $lowerName === 'id') {
            return 1;
        }

        return match ($schema->type) {
            FieldType::Boolean => true,
            FieldType::Integer => 1,
            FieldType::Number => 1.5,
            FieldType::Array => [],
            FieldType::Object => [],
            FieldType::File => 'example.pdf',
            FieldType::String => 'Sample '.$schema->name,
        };
    }
}
