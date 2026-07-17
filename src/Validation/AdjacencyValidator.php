<?php

declare(strict_types=1);

namespace Crosseno\Core\Validation;

use Crosseno\Core\Crossword\Crossword;

final readonly class AdjacencyValidator implements Validator
{
    public function validate(Crossword $crossword, ValidationProfile $profile): array
    {
        if ($profile->adjacency === AdjacencyPolicy::Allow) {
            return [];
        }

        $owners = [];
        $positions = [];
        foreach ($crossword->entries() as $entryIndex => $entry) {
            foreach ($entry->placement->positions() as $position) {
                $owners[$position->key()][$entryIndex] = true;
                $positions[$position->key()] = $position;
            }
        }

        ksort($positions, SORT_NATURAL);
        $violations = [];
        foreach ($positions as $position) {
            foreach ([[0, 1], [1, 0]] as [$rowDelta, $columnDelta]) {
                $neighborRow = $position->row + $rowDelta;
                $neighborColumn = $position->column + $columnDelta;
                if ($neighborRow >= $crossword->grid->dimensions->rows
                    || $neighborColumn >= $crossword->grid->dimensions->columns) {
                    continue;
                }
                $neighborKey = $neighborRow . ':' . $neighborColumn;
                if (!isset($owners[$neighborKey])) {
                    continue;
                }

                if (array_intersect_key($owners[$position->key()], $owners[$neighborKey]) === []) {
                    $violations[] = new Violation(
                        ViolationCode::AdjacentEntries,
                        'Cells from unrelated entries touch orthogonally.',
                        'grid.cells.' . $position->key(),
                        [
                            'row' => $position->row,
                            'column' => $position->column,
                            'neighborRow' => $neighborRow,
                            'neighborColumn' => $neighborColumn,
                        ],
                    );
                }
            }
        }

        return $violations;
    }
}
