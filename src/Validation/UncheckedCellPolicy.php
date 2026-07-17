<?php

declare(strict_types=1);

namespace Crosseno\Core\Validation;

enum UncheckedCellPolicy: string
{
    case Allow = 'allow';
    case Forbid = 'forbid';
}
