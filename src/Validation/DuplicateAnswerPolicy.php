<?php

declare(strict_types=1);

namespace Crosseno\Core\Validation;

enum DuplicateAnswerPolicy: string
{
    case Allow = 'allow';
    case Forbid = 'forbid';
}
