<?php

declare(strict_types=1);

namespace Crosseno\Core\Grid;

use Crosseno\Core\Exception\InvalidDomainValue;

final readonly class CellSymbol implements \Stringable
{
    public function __construct(public string $value)
    {
        if ($value === '') {
            throw new InvalidDomainValue('A cell symbol cannot be empty.');
        }

        if (preg_match('//u', $value) !== 1) {
            throw new InvalidDomainValue('A cell symbol must be valid UTF-8.');
        }

        if (preg_match('/\s/u', $value) === 1) {
            throw new InvalidDomainValue('A cell symbol cannot contain whitespace.');
        }
    }

    public function equals(self $other): bool
    {
        // Core deliberately compares exact code-point sequences and never normalizes.
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
