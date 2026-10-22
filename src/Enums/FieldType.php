<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Enums;

enum FieldType: string
{
    case String = 'string';
    case Integer = 'integer';
    case Number = 'number';
    case Boolean = 'boolean';
    case Array = 'array';
    case Object = 'object';
    case File = 'file';
}
