<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Contracts;

use Isaacjuwon\Probe\Data\FieldSchema;

interface RuleMapper
{
    public function canHandle(string $rule): bool;

    /**
     * @param  list<string>  $parameters
     */
    public function map(string $field, string $rule, array $parameters, FieldSchema $current): FieldSchema;
}
