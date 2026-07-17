<?php

declare(strict_types=1);

namespace Crosseno\Core;

use Crosseno\Core\Exception\InvalidDomainValue;
use Crosseno\Core\Exception\ResourceLimitExceeded;
use Crosseno\Core\Grid\GridDimensions;

final readonly class ResourceLimits
{
    public function __construct(
        public int $maxRows,
        public int $maxColumns,
        public int $maxGridCells,
        public int $maxEntryLength,
        public int $maxOccupiedCells,
        public int $maxSnapshotBytes,
    ) {
        foreach (get_object_vars($this) as $name => $value) {
            if ($value < 1) {
                throw new InvalidDomainValue(\sprintf('%s must be positive.', $name));
            }
        }
    }

    public static function standard(): self
    {
        return new self(100, 100, 10_000, 100, 10_000, 10_000_000);
    }

    public function assertDimensions(GridDimensions $dimensions): void
    {
        if ($dimensions->rows > $this->maxRows || $dimensions->columns > $this->maxColumns) {
            throw new ResourceLimitExceeded('Grid dimensions exceed the configured row or column limit.');
        }

        if ($dimensions->cellCount() > $this->maxGridCells) {
            throw new ResourceLimitExceeded('Grid cell count exceeds the configured limit.');
        }
    }

    public function assertEntryLength(int $length): void
    {
        if ($length > $this->maxEntryLength) {
            throw new ResourceLimitExceeded('Entry length exceeds the configured limit.');
        }
    }

    public function assertOccupiedCells(int $count): void
    {
        if ($count > $this->maxOccupiedCells) {
            throw new ResourceLimitExceeded('Occupied cell count exceeds the configured limit.');
        }
    }
}
