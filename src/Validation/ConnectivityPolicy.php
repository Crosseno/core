<?php

declare(strict_types=1);

namespace Crosseno\Core\Validation;

enum ConnectivityPolicy: string
{
    case Ignore = 'ignore';
    case RequireConnected = 'require_connected';
}
