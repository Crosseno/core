<?php

declare(strict_types=1);

namespace Crosseno\Core\Validation;

enum AdjacencyPolicy: string
{
    case Allow = 'allow';
    case ForbidUnsharedOrthogonalTouching = 'forbid_unshared_orthogonal_touching';
}
