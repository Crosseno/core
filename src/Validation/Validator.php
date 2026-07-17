<?php

declare(strict_types=1);

namespace Crosseno\Core\Validation;

use Crosseno\Core\Crossword\Crossword;

interface Validator
{
    /** @return list<Violation> */
    public function validate(Crossword $crossword, ValidationProfile $profile): array;
}
