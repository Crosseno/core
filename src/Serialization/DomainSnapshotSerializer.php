<?php

declare(strict_types=1);

namespace Crosseno\Core\Serialization;

use Crosseno\Core\Crossword\Crossword;
use Crosseno\Core\Crossword\CrosswordEntry;
use Crosseno\Core\Exception\ResourceLimitExceeded;
use Crosseno\Core\Exception\SerializationFailed;
use Crosseno\Core\Grid\CellState;
use Crosseno\Core\Grid\CellStateType;
use Crosseno\Core\ResourceLimits;

final readonly class DomainSnapshotSerializer
{
    public const SCHEMA_NAME = 'crosseno-core-snapshot';
    public const SCHEMA_VERSION = 1;

    public function serialize(Crossword $crossword, ResourceLimits $limits): string
    {
        $limits->assertDimensions($crossword->grid->dimensions);
        $limits->assertOccupiedCells($crossword->grid->occupiedCellCount());

        $entries = $crossword->entries();
        usort($entries, self::compareEntries(...));

        $payload = [
            'schema' => self::SCHEMA_NAME,
            'version' => self::SCHEMA_VERSION,
            'duplicatePlacementPolicy' => $crossword->duplicatePlacementPolicy->value,
            'grid' => [
                'rows' => $crossword->grid->dimensions->rows,
                'columns' => $crossword->grid->dimensions->columns,
                'cells' => array_map(self::serializeCell(...), $crossword->grid->cells()),
            ],
            'entries' => array_map(self::serializeEntry(...), $entries),
        ];

        try {
            $json = json_encode(
                $payload,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            );
        } catch (\JsonException $exception) {
            throw new SerializationFailed('The core snapshot could not be encoded.', previous: $exception);
        }

        if (\strlen($json) > $limits->maxSnapshotBytes) {
            throw new ResourceLimitExceeded('Serialized snapshot exceeds the configured byte limit.');
        }

        return $json;
    }

    /** @return array{state: string, symbol: ?string} */
    private static function serializeCell(CellState $cell): array
    {
        return ['state' => $cell->type->value, 'symbol' => $cell->symbol?->value];
    }

    /**
     * @return array{
     *   answer: array{key: string, displayText: string, cells: non-empty-list<string>},
     *   senseKey: ?string,
     *   placement: array{row: int, column: int, direction: string, length: int}
     * }
     */
    private static function serializeEntry(CrosswordEntry $entry): array
    {
        return [
            'answer' => [
                'key' => $entry->answer->key->value,
                'displayText' => $entry->answer->displayText,
                'cells' => array_map(
                    static fn($symbol): string => $symbol->value,
                    $entry->answer->cells(),
                ),
            ],
            'senseKey' => $entry->senseKey?->value,
            'placement' => [
                'row' => $entry->placement->start->row,
                'column' => $entry->placement->start->column,
                'direction' => $entry->placement->direction->value,
                'length' => $entry->placement->length,
            ],
        ];
    }

    private static function compareEntries(CrosswordEntry $left, CrosswordEntry $right): int
    {
        return [
            $left->placement->start->row,
            $left->placement->start->column,
            $left->placement->direction->value,
            $left->answer->key->value,
            $left->senseKey === null ? '' : $left->senseKey->value,
            $left->answer->displayText,
            array_map(static fn($symbol): string => $symbol->value, $left->answer->cells()),
        ] <=> [
            $right->placement->start->row,
            $right->placement->start->column,
            $right->placement->direction->value,
            $right->answer->key->value,
            $right->senseKey === null ? '' : $right->senseKey->value,
            $right->answer->displayText,
            array_map(static fn($symbol): string => $symbol->value, $right->answer->cells()),
        ];
    }
}
