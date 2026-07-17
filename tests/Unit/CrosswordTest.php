<?php

declare(strict_types=1);

namespace Crosseno\Core\Tests\Unit;

use Crosseno\Core\Crossword\DuplicatePlacementPolicy;
use Crosseno\Core\Exception\GridConflict;
use Crosseno\Core\Exception\OutOfBounds;
use Crosseno\Core\Grid\Direction;
use Crosseno\Core\Grid\Position;
use Crosseno\Core\ResourceLimits;
use Crosseno\Core\Tests\Support\DomainFactory;
use PHPUnit\Framework\TestCase;

final class CrosswordTest extends TestCase
{
    public function testMatchingCrossingBuildsOneSharedCell(): void
    {
        $limits = ResourceLimits::standard();
        $crossword = DomainFactory::crossword([
            DomainFactory::entry('cat', ['C', 'A', 'T'], 1, 0, Direction::Horizontal, $limits),
            DomainFactory::entry('ear', ['E', 'A', 'R'], 0, 1, Direction::Vertical, $limits),
        ], $limits);

        self::assertSame('A', $crossword->grid->cell(new Position(1, 1))->symbol?->value);
        self::assertSame(5, $crossword->grid->occupiedCellCount());
    }

    public function testConflictingCrossingIsRejected(): void
    {
        $limits = ResourceLimits::standard();

        $this->expectException(GridConflict::class);
        DomainFactory::crossword([
            DomainFactory::entry('cat', ['C', 'A', 'T'], 1, 0, Direction::Horizontal, $limits),
            DomainFactory::entry('eor', ['E', 'O', 'R'], 0, 1, Direction::Vertical, $limits),
        ], $limits);
    }

    public function testEntryMustFitGrid(): void
    {
        $limits = ResourceLimits::standard();

        $this->expectException(OutOfBounds::class);
        DomainFactory::crossword([
            DomainFactory::entry('cat', ['C', 'A', 'T'], 0, 1, Direction::Horizontal, $limits),
        ], $limits, rows: 2, columns: 2);
    }

    public function testDuplicatePlacementPolicyIsEnforced(): void
    {
        $limits = ResourceLimits::standard();
        $entries = [
            DomainFactory::entry('one', ['A', 'B'], 0, 0, Direction::Horizontal, $limits),
            DomainFactory::entry('two', ['A', 'B'], 0, 0, Direction::Horizontal, $limits),
        ];

        $this->expectException(GridConflict::class);
        DomainFactory::crossword($entries, $limits, policy: DuplicatePlacementPolicy::Forbid);
    }

    public function testDuplicatePlacementCanBeAllowedExplicitly(): void
    {
        $limits = ResourceLimits::standard();
        $entries = [
            DomainFactory::entry('one', ['A', 'B'], 0, 0, Direction::Horizontal, $limits),
            DomainFactory::entry('two', ['A', 'B'], 0, 0, Direction::Horizontal, $limits),
        ];

        $crossword = DomainFactory::crossword($entries, $limits, policy: DuplicatePlacementPolicy::Allow);

        self::assertCount(2, $crossword->entries());
    }
}
