<?php

declare(strict_types=1);

namespace Crosseno\Core\Validation;

enum SymmetryPolicy: string
{
    case None = 'none';
    case Rotational180 = 'rotational_180';
}
