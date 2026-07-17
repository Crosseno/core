<?php

declare(strict_types=1);

namespace Crosseno\Core\Answer;

use Crosseno\Core\Exception\InvalidDomainValue;

final readonly class AnswerKey implements \Stringable
{
    public function __construct(public string $value)
    {
        self::validate($value, 'Answer');
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private static function validate(string $value, string $label): void
    {
        if ($value === '' || \strlen($value) > 255 || preg_match('//u', $value) !== 1) {
            throw new InvalidDomainValue($label . ' keys must be non-empty valid UTF-8 of at most 255 bytes.');
        }

        if (preg_match('/[\p{C}\s]/u', $value) === 1) {
            throw new InvalidDomainValue($label . ' keys cannot contain whitespace or control characters.');
        }
    }
}
