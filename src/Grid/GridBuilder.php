<?php

declare(strict_types=1);

namespace Crosseno\Core\Grid;

use Crosseno\Core\ResourceLimits;

final class GridBuilder
{
    private Grid $grid;

    public function __construct(GridDimensions $dimensions, private readonly ResourceLimits $limits)
    {
        $this->grid = Grid::empty($dimensions, $limits);
    }

    public function set(Position $position, CellState $state): self
    {
        $this->grid = $this->grid->withCell($position, $state, $this->limits);

        return $this;
    }

    public function build(): Grid
    {
        return $this->grid;
    }
}
