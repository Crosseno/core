<?php

declare(strict_types=1);

namespace Crosseno\Core\Tests\Unit;

use Crosseno\Core\Exception\InvalidDomainValue;
use Crosseno\Core\Exception\ResourceLimitExceeded;
use Crosseno\Core\Grid\Direction;
use Crosseno\Core\Grid\Grid;
use Crosseno\Core\Grid\GridDimensions;
use Crosseno\Core\Grid\Position;
use Crosseno\Core\ResourceLimits;
use PHPUnit\Framework\TestCase;

final class GridValueObjectsTest extends TestCase
{
    public function testPositionsAreZeroBasedAndMoveByDirection(): void
    {
        $position = new Position(2, 3);

        self::assertEquals(new Position(2, 5), $position->move(Direction::Horizontal, 2));
        self::assertEquals(new Position(4, 3), $position->move(Direction::Vertical, 2));
        self::assertSame(Direction::Vertical, Direction::Horizontal->perpendicular());
    }

    public function testNegativePositionIsRejected(): void
    {
        $this->expectException(InvalidDomainValue::class);
        new Position(-1, 0);
    }

    public function testDimensionsMustBePositive(): void
    {
        $this->expectException(InvalidDomainValue::class);
        new GridDimensions(0, 3);
    }

    public function testDimensionsAreCheckedBeforeGridAllocation(): void
    {
        $limits = new ResourceLimits(2, 2, 4, 4, 4, 1_000);

        $this->expectException(ResourceLimitExceeded::class);
        Grid::empty(new GridDimensions(3, 2), $limits);
    }

    public function testBoundsAreExplicit(): void
    {
        $dimensions = new GridDimensions(2, 3);

        self::assertTrue($dimensions->contains(new Position(1, 2)));
        self::assertFalse($dimensions->contains(new Position(2, 2)));
    }
}
