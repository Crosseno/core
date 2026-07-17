<?php

declare(strict_types=1);

namespace Crosseno\Core\Grid;

use Crosseno\Core\Exception\InvalidDomainValue;
use Crosseno\Core\Exception\OutOfBounds;
use Crosseno\Core\ResourceLimits;

final readonly class Grid
{
    /** @var list<CellState> */
    private array $cells;

    /**
     * @param list<CellState> $cells Cells in deterministic row-major order.
     */
    public function __construct(
        public GridDimensions $dimensions,
        array $cells,
        ResourceLimits $limits,
    ) {
        $limits->assertDimensions($dimensions);

        if (!array_is_list($cells) || \count($cells) !== $dimensions->cellCount()) {
            throw new InvalidDomainValue('The grid must contain exactly one state per cell in row-major order.');
        }

        $occupied = 0;
        foreach ($cells as $cell) {
            if (!$cell instanceof CellState) {
                throw new InvalidDomainValue('Every grid cell must be a CellState.');
            }
            $occupied += (int) ($cell->type === CellStateType::Filled);
        }
        $limits->assertOccupiedCells($occupied);
        $this->cells = $cells;
    }

    public static function empty(GridDimensions $dimensions, ResourceLimits $limits): self
    {
        $limits->assertDimensions($dimensions);

        return new self(
            $dimensions,
            array_fill(0, $dimensions->cellCount(), CellState::empty()),
            $limits,
        );
    }

    public function cell(Position $position): CellState
    {
        if (!$this->dimensions->contains($position)) {
            throw new OutOfBounds(\sprintf('Position %s is outside the grid.', $position->key()));
        }

        return $this->cells[$this->offset($position)];
    }

    public function withCell(Position $position, CellState $state, ResourceLimits $limits): self
    {
        if (!$this->dimensions->contains($position)) {
            throw new OutOfBounds(\sprintf('Position %s is outside the grid.', $position->key()));
        }

        $current = $this->cell($position);
        if ($current->type !== CellStateType::Filled && $state->type === CellStateType::Filled) {
            $limits->assertOccupiedCells($this->occupiedCellCount() + 1);
        }

        $cells = $this->cells;
        $cells[$this->offset($position)] = $state;

        return new self($this->dimensions, array_values($cells), $limits);
    }

    /** @return list<CellState> */
    public function cells(): array
    {
        return $this->cells;
    }

    public function occupiedCellCount(): int
    {
        return \count(array_filter(
            $this->cells,
            static fn(CellState $cell): bool => $cell->type === CellStateType::Filled,
        ));
    }

    private function offset(Position $position): int
    {
        return ($position->row * $this->dimensions->columns) + $position->column;
    }
}
