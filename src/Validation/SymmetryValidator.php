<?php

declare(strict_types=1);

namespace Crosseno\Core\Validation;

use Crosseno\Core\Crossword\Crossword;
use Crosseno\Core\Grid\CellStateType;
use Crosseno\Core\Grid\Position;

final readonly class SymmetryValidator implements Validator
{
    public function validate(Crossword $crossword, ValidationProfile $profile): array
    {
        if ($profile->symmetry === SymmetryPolicy::None) {
            return [];
        }

        $dimensions = $crossword->grid->dimensions;
        $violations = [];
        for ($row = 0; $row < $dimensions->rows; ++$row) {
            for ($column = 0; $column < $dimensions->columns; ++$column) {
                $mirrorRow = $dimensions->rows - 1 - $row;
                $mirrorColumn = $dimensions->columns - 1 - $column;
                if ($row > $mirrorRow || ($row === $mirrorRow && $column >= $mirrorColumn)) {
                    continue;
                }

                $blocked = $crossword->grid->cell(new Position($row, $column))->type === CellStateType::Blocked;
                $mirrorBlocked = $crossword->grid->cell(new Position($mirrorRow, $mirrorColumn))->type === CellStateType::Blocked;
                if ($blocked !== $mirrorBlocked) {
                    $violations[] = new Violation(
                        ViolationCode::SymmetryMismatch,
                        'Blocked-cell pattern is not rotationally symmetric.',
                        \sprintf('grid.cells.%d:%d', $row, $column),
                        ['row' => $row, 'column' => $column, 'mirrorRow' => $mirrorRow, 'mirrorColumn' => $mirrorColumn],
                    );
                }
            }
        }

        return $violations;
    }
}
