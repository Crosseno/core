<?php

declare(strict_types=1);

namespace Crosseno\Core\Crossword;

use Crosseno\Core\Answer\Answer;
use Crosseno\Core\Answer\SenseKey;
use Crosseno\Core\Exception\InvalidDomainValue;
use Crosseno\Core\Grid\Placement;

final readonly class CrosswordEntry
{
    public function __construct(
        public Answer $answer,
        public Placement $placement,
        public ?SenseKey $senseKey = null,
    ) {
        if ($answer->length() !== $placement->length) {
            throw new InvalidDomainValue('Answer and placement lengths must match.');
        }
    }
}
