<?php

declare(strict_types=1);

namespace Crosseno\Core\Grid;

enum Direction: string
{
    case Horizontal = 'horizontal';
    case Vertical = 'vertical';

    public function rowDelta(): int
    {
        return match ($this) {
            self::Horizontal => 0,
            self::Vertical => 1,
        };
    }

    public function columnDelta(): int
    {
        return match ($this) {
            self::Horizontal => 1,
            self::Vertical => 0,
        };
    }

    public function perpendicular(): self
    {
        return match ($this) {
            self::Horizontal => self::Vertical,
            self::Vertical => self::Horizontal,
        };
    }
}
