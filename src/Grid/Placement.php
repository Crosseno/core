<?php

declare(strict_types=1);

namespace Crosseno\Core\Grid;

use Crosseno\Core\Exception\InvalidDomainValue;
use Crosseno\Core\ResourceLimits;

final readonly class Placement
{
    public function __construct(
        public Position $start,
        public Direction $direction,
        public int $length,
        ResourceLimits $limits,
    ) {
        if ($length < 1) {
            throw new InvalidDomainValue('Placement length must be positive.');
        }

        $limits->assertEntryLength($length);
    }

    /** @return non-empty-list<Position> */
    public function positions(): array
    {
        $positions = [$this->start];
        for ($offset = 1; $offset < $this->length; ++$offset) {
            $positions[] = $this->start->move($this->direction, $offset);
        }

        return $positions;
    }

    public function end(): Position
    {
        return $this->start->move($this->direction, $this->length - 1);
    }

    public function contains(Position $position): bool
    {
        if ($this->direction === Direction::Horizontal) {
            return $position->row === $this->start->row
                && $position->column >= $this->start->column
                && $position->column <= $this->end()->column;
        }

        return $position->column === $this->start->column
            && $position->row >= $this->start->row
            && $position->row <= $this->end()->row;
    }

    public function signature(): string
    {
        return $this->start->key() . ':' . $this->direction->value . ':' . $this->length;
    }
}
