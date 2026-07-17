<?php

declare(strict_types=1);

namespace Crosseno\Core\Crossword;

enum DuplicatePlacementPolicy: string
{
    case Forbid = 'forbid';
    case Allow = 'allow';
}
