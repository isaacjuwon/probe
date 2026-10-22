<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Enums;

enum AuthType: string
{
    case None = 'none';
    case Bearer = 'bearer';
    case Basic = 'basic';
    case ApiKey = 'api_key';
    case Session = 'session';
    case SanctumActAs = 'sanctum_act_as';
}
