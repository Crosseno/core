<?php

declare(strict_types=1);

namespace Crosseno\Core\Tests\Unit;

use Crosseno\Core\Exception\OutOfBounds;
use Crosseno\Core\Exception\ResourceLimitExceeded;
use Crosseno\Core\Grid\CellState;
use Crosseno\Core\Grid\CellStateType;
use Crosseno\Core\Grid\CellSymbol;
use Crosseno\Core\Grid\Direction;
use Crosseno\Core\Grid\Grid;
use Crosseno\Core\Grid\GridDimensions;
use Crosseno\Core\Grid\Placement;
use Crosseno\Core\Grid\Position;
use Crosseno\Core\ResourceLimits;
use PHPUnit\Framework\TestCase;

final class PlacementAndGridTest extends TestCase
{
    public function testPlacementsEnumeratePositionsDeterministically(): void
    {
        $limits = ResourceLimits::standard();
        $horizontal = new Placement(new Position(1, 2), Direction::Horizontal, 3, $limits);
        $vertical = new Placement(new Position(1, 2), Direction::Vertical, 3, $limits);

        self::assertSame(['1:2', '1:3', '1:4'], array_map(static fn(Position $position): string => $position->key(), $horizontal->positions()));
        self::assertSame(['1:2', '2:2', '3:2'], array_map(static fn(Position $position): string => $position->key(), $vertical->positions()));
    }

    public function testGridUpdatesAreImmutable(): void
    {
        $limits = ResourceLimits::standard();
        $position = new Position(0, 0);
        $original = Grid::empty(new GridDimensions(2, 2), $limits);
        $changed = $original->withCell($position, CellState::filled(new CellSymbol('Ł')), $limits);

        self::assertSame(CellStateType::Empty, $original->cell($position)->type);
        self::assertSame('Ł', $changed->cell($position)->symbol?->value);
    }

    public function testGridRejectsOutOfBoundsAccess(): void
    {
        $grid = Grid::empty(new GridDimensions(2, 2), ResourceLimits::standard());

        $this->expectException(OutOfBounds::class);
        $grid->cell(new Position(2, 0));
    }

    public function testOccupiedCellLimitIsCheckedBeforeAnImmutableUpdate(): void
    {
        $limits = new ResourceLimits(2, 2, 4, 2, 1, 1_000);
        $grid = Grid::empty(new GridDimensions(2, 2), $limits)
            ->withCell(new Position(0, 0), CellState::filled(new CellSymbol('A')), $limits);

        $this->expectException(ResourceLimitExceeded::class);
        $grid->withCell(new Position(0, 1), CellState::filled(new CellSymbol('B')), $limits);
    }
}
