<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Contracts;

use Isaacjuwon\Probe\Data\FieldSchema;

interface ExampleGeneratorContract
{
    public function generate(FieldSchema $schema): mixed;
}
