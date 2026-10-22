<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Data;

use Isaacjuwon\Probe\Enums\FieldType;

final readonly class FieldSchema
{
    /**
     * @param  list<string>  $rules
     * @param  array<string, mixed>  $constraints
     */
    public function __construct(
        public string $name,
        public FieldType $type = FieldType::String,
        public bool $required = false,
        public bool $nullable = false,
        public array $rules = [],
        public array $constraints = [],
        public mixed $example = null,
        public ?string $description = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type->value,
            'required' => $this->required,
            'nullable' => $this->nullable,
            'rules' => $this->rules,
            'constraints' => $this->constraints,
            'example' => $this->example,
            'description' => $this->description,
        ];
    }
}
