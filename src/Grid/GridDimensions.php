<?php

declare(strict_types=1);

namespace Crosseno\Core\Grid;

use Crosseno\Core\Exception\InvalidDomainValue;

final readonly class GridDimensions
{
    public function __construct(public int $rows, public int $columns)
    {
        if ($rows < 1 || $columns < 1) {
            throw new InvalidDomainValue('Grid dimensions must be positive.');
        }

        if ($rows > intdiv(PHP_INT_MAX, $columns)) {
            throw new InvalidDomainValue('Grid dimensions overflow the platform integer size.');
        }
    }

    public function contains(Position $position): bool
    {
        return $position->row < $this->rows && $position->column < $this->columns;
    }

    public function cellCount(): int
    {
        return $this->rows * $this->columns;
    }
}
