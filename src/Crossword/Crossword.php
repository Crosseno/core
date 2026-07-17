<?php

declare(strict_types=1);

namespace Crosseno\Core\Crossword;

use Crosseno\Core\Exception\GridConflict;
use Crosseno\Core\Exception\InvalidDomainValue;
use Crosseno\Core\Exception\OutOfBounds;
use Crosseno\Core\Grid\CellState;
use Crosseno\Core\Grid\CellStateType;
use Crosseno\Core\Grid\Grid;
use Crosseno\Core\ResourceLimits;

final readonly class Crossword
{
    /** @var list<CrosswordEntry> */
    private array $entries;

    public Grid $grid;

    /**
     * @param list<CrosswordEntry> $entries
     */
    public function __construct(
        Grid $grid,
        array $entries,
        public DuplicatePlacementPolicy $duplicatePlacementPolicy,
        ResourceLimits $limits,
    ) {
        if (!array_is_list($entries)) {
            throw new InvalidDomainValue('Crossword entries must be a list.');
        }

        $limits->assertDimensions($grid->dimensions);
        $cells = $grid->cells();
        $placementSignatures = [];
        $occupiedCellCount = $grid->occupiedCellCount();

        foreach ($entries as $entryIndex => $entry) {
            if (!$entry instanceof CrosswordEntry) {
                throw new InvalidDomainValue('Every crossword entry must be a CrosswordEntry.');
            }

            $signature = $entry->placement->signature();
            if ($duplicatePlacementPolicy === DuplicatePlacementPolicy::Forbid && isset($placementSignatures[$signature])) {
                throw new GridConflict(\sprintf('Entry %d duplicates an existing placement.', $entryIndex));
            }
            $placementSignatures[$signature] = true;

            foreach ($entry->placement->positions() as $cellIndex => $position) {
                if (!$grid->dimensions->contains($position)) {
                    throw new OutOfBounds(\sprintf('Entry %d extends outside the grid at %s.', $entryIndex, $position->key()));
                }

                $offset = ($position->row * $grid->dimensions->columns) + $position->column;
                $existing = $cells[$offset];
                $symbol = $entry->answer->cells()[$cellIndex];

                if ($existing->type === CellStateType::Blocked) {
                    throw new GridConflict(\sprintf('Entry %d occupies blocked cell %s.', $entryIndex, $position->key()));
                }
                if ($existing->type === CellStateType::Filled && !$existing->symbol?->equals($symbol)) {
                    throw new GridConflict(\sprintf('Entry %d conflicts with the symbol at %s.', $entryIndex, $position->key()));
                }

                if ($existing->type === CellStateType::Empty) {
                    $limits->assertOccupiedCells($occupiedCellCount + 1);
                    ++$occupiedCellCount;
                }
                $cells[$offset] = CellState::filled($symbol);
            }
        }

        $this->grid = new Grid($grid->dimensions, array_values($cells), $limits);
        $this->entries = $entries;
    }

    /** @return list<CrosswordEntry> */
    public function entries(): array
    {
        return $this->entries;
    }
}
