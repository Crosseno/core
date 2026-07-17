<?php

declare(strict_types=1);

namespace Crosseno\Core\Answer;

use Crosseno\Core\Exception\InvalidDomainValue;

final readonly class SenseKey implements \Stringable
{
    public function __construct(public string $value)
    {
        if ($value === '' || \strlen($value) > 255 || preg_match('//u', $value) !== 1) {
            throw new InvalidDomainValue('Sense keys must be non-empty valid UTF-8 of at most 255 bytes.');
        }

        if (preg_match('/[\p{C}\s]/u', $value) === 1) {
            throw new InvalidDomainValue('Sense keys cannot contain whitespace or control characters.');
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
