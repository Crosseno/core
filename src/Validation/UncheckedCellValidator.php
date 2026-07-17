<?php

declare(strict_types=1);

namespace Crosseno\Core\Validation;

use Crosseno\Core\Crossword\Crossword;
use Crosseno\Core\Grid\CellStateType;
use Crosseno\Core\Grid\Position;

final readonly class UncheckedCellValidator implements Validator
{
    public function validate(Crossword $crossword, ValidationProfile $profile): array
    {
        if ($profile->uncheckedCells === UncheckedCellPolicy::Allow) {
            return [];
        }

        $coverage = [];
        foreach ($crossword->entries() as $entry) {
            foreach ($entry->placement->positions() as $position) {
                $coverage[$position->key()] = ($coverage[$position->key()] ?? 0) + 1;
            }
        }

        $violations = [];
        for ($row = 0; $row < $crossword->grid->dimensions->rows; ++$row) {
            for ($column = 0; $column < $crossword->grid->dimensions->columns; ++$column) {
                $position = new Position($row, $column);
                if ($crossword->grid->cell($position)->type === CellStateType::Filled
                    && ($coverage[$position->key()] ?? 0) < 2) {
                    $violations[] = new Violation(
                        ViolationCode::UncheckedCell,
                        'Filled cell is not checked by two entries.',
                        'grid.cells.' . $position->key(),
                        ['row' => $row, 'column' => $column, 'coverage' => $coverage[$position->key()] ?? 0],
                    );
                }
            }
        }

        return $violations;
    }
}
