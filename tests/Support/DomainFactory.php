<?php

declare(strict_types=1);

namespace Crosseno\Core\Tests\Support;

use Crosseno\Core\Answer\Answer;
use Crosseno\Core\Answer\AnswerKey;
use Crosseno\Core\Crossword\Crossword;
use Crosseno\Core\Crossword\CrosswordEntry;
use Crosseno\Core\Crossword\DuplicatePlacementPolicy;
use Crosseno\Core\Grid\CellSymbol;
use Crosseno\Core\Grid\Direction;
use Crosseno\Core\Grid\Grid;
use Crosseno\Core\Grid\GridDimensions;
use Crosseno\Core\Grid\Placement;
use Crosseno\Core\Grid\Position;
use Crosseno\Core\ResourceLimits;

final class DomainFactory
{
    /** @param non-empty-list<string> $cells */
    public static function answer(string $key, array $cells, ResourceLimits $limits, ?string $display = null): Answer
    {
        return new Answer(
            new AnswerKey($key),
            array_map(static fn(string $cell): CellSymbol => new CellSymbol($cell), $cells),
            $display ?? implode('', $cells),
            $limits,
        );
    }

    /**
     * @param non-empty-list<string> $cells
     */
    public static function entry(
        string $key,
        array $cells,
        int $row,
        int $column,
        Direction $direction,
        ResourceLimits $limits,
    ): CrosswordEntry {
        return new CrosswordEntry(
            self::answer($key, $cells, $limits),
            new Placement(new Position($row, $column), $direction, \count($cells), $limits),
        );
    }

    /** @param list<CrosswordEntry> $entries */
    public static function crossword(
        array $entries,
        ResourceLimits $limits,
        int $rows = 5,
        int $columns = 5,
        DuplicatePlacementPolicy $policy = DuplicatePlacementPolicy::Forbid,
    ): Crossword {
        return new Crossword(
            Grid::empty(new GridDimensions($rows, $columns), $limits),
            $entries,
            $policy,
            $limits,
        );
    }
}
