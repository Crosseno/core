<?php

declare(strict_types=1);

namespace Crosseno\Core\Grid;

use Crosseno\Core\Exception\InvalidDomainValue;

final readonly class Position
{
    public function __construct(public int $row, public int $column)
    {
        if ($row < 0 || $column < 0) {
            throw new InvalidDomainValue('Grid positions must be zero-based and non-negative.');
        }
    }

    public function move(Direction $direction, int $steps = 1): self
    {
        if ($steps < 0) {
            throw new InvalidDomainValue('Movement steps must be non-negative.');
        }

        return new self(
            $this->row + ($direction->rowDelta() * $steps),
            $this->column + ($direction->columnDelta() * $steps),
        );
    }

    public function key(): string
    {
        return $this->row . ':' . $this->column;
    }

    public function equals(self $other): bool
    {
        return $this->row === $other->row && $this->column === $other->column;
    }
}
