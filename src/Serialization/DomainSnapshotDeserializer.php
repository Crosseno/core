<?php

declare(strict_types=1);

namespace Crosseno\Core\Serialization;

use Crosseno\Core\Answer\Answer;
use Crosseno\Core\Answer\AnswerKey;
use Crosseno\Core\Answer\SenseKey;
use Crosseno\Core\Crossword\Crossword;
use Crosseno\Core\Crossword\CrosswordEntry;
use Crosseno\Core\Crossword\DuplicatePlacementPolicy;
use Crosseno\Core\Exception\CoreException;
use Crosseno\Core\Exception\ResourceLimitExceeded;
use Crosseno\Core\Exception\SerializationFailed;
use Crosseno\Core\Exception\UnsupportedSchemaVersion;
use Crosseno\Core\Grid\CellState;
use Crosseno\Core\Grid\CellStateType;
use Crosseno\Core\Grid\CellSymbol;
use Crosseno\Core\Grid\Direction;
use Crosseno\Core\Grid\Grid;
use Crosseno\Core\Grid\GridDimensions;
use Crosseno\Core\Grid\Placement;
use Crosseno\Core\Grid\Position;
use Crosseno\Core\ResourceLimits;

final readonly class DomainSnapshotDeserializer
{
    public function deserialize(string $json, ResourceLimits $limits): Crossword
    {
        if (\strlen($json) > $limits->maxSnapshotBytes) {
            throw new ResourceLimitExceeded('Snapshot exceeds the configured byte limit.');
        }

        try {
            $payload = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new SerializationFailed('The core snapshot is not valid JSON.', previous: $exception);
        }

        try {
            $root = self::object($payload, 'snapshot');
            self::exactKeys($root, ['schema', 'version', 'duplicatePlacementPolicy', 'grid', 'entries'], 'snapshot');

            if (self::string($root['schema'] ?? null, 'schema') !== DomainSnapshotSerializer::SCHEMA_NAME) {
                throw new SerializationFailed('The payload is not a Crosseno core snapshot.');
            }

            $version = self::integer($root['version'] ?? null, 'version');
            if ($version !== DomainSnapshotSerializer::SCHEMA_VERSION) {
                throw new UnsupportedSchemaVersion(\sprintf('Unsupported core snapshot version: %d.', $version));
            }

            $gridData = self::object($root['grid'] ?? null, 'grid');
            self::exactKeys($gridData, ['rows', 'columns', 'cells'], 'grid');
            $dimensions = new GridDimensions(
                self::integer($gridData['rows'] ?? null, 'grid.rows'),
                self::integer($gridData['columns'] ?? null, 'grid.columns'),
            );
            // Limits are checked before converting the untrusted cell payload into domain objects.
            $limits->assertDimensions($dimensions);

            $cellData = self::list($gridData['cells'] ?? null, 'grid.cells');
            if (\count($cellData) !== $dimensions->cellCount()) {
                throw new SerializationFailed('grid.cells does not match the declared dimensions.');
            }
            $cells = [];
            $occupied = 0;
            foreach ($cellData as $index => $item) {
                $cell = self::deserializeCell($item, \sprintf('grid.cells[%d]', $index));
                $occupied += (int) ($cell->type === CellStateType::Filled);
                $limits->assertOccupiedCells($occupied);
                $cells[] = $cell;
            }
            $grid = new Grid($dimensions, $cells, $limits);

            $entryData = self::list($root['entries'] ?? null, 'entries');
            if (\count($entryData) > $limits->maxOccupiedCells) {
                throw new ResourceLimitExceeded('Entry count exceeds the configured occupied-cell limit.');
            }
            $entries = [];
            foreach ($entryData as $index => $item) {
                $entries[] = self::deserializeEntry($item, \sprintf('entries[%d]', $index), $limits);
            }

            $policyValue = self::string($root['duplicatePlacementPolicy'] ?? null, 'duplicatePlacementPolicy');
            $policy = DuplicatePlacementPolicy::tryFrom($policyValue)
                ?? throw new SerializationFailed('duplicatePlacementPolicy has an unknown value.');

            return new Crossword($grid, $entries, $policy, $limits);
        } catch (UnsupportedSchemaVersion|ResourceLimitExceeded|SerializationFailed $exception) {
            throw $exception;
        } catch (CoreException|\ValueError $exception) {
            throw new SerializationFailed('The core snapshot contains invalid domain data.', previous: $exception);
        }
    }

    private static function deserializeCell(mixed $value, string $path): CellState
    {
        $data = self::object($value, $path);
        self::exactKeys($data, ['state', 'symbol'], $path);
        $state = self::string($data['state'] ?? null, $path . '.state');
        $symbol = $data['symbol'] ?? null;

        return match ($state) {
            CellStateType::Empty->value => $symbol === null
                ? CellState::empty()
                : throw new SerializationFailed($path . '.symbol must be null for an empty cell.'),
            CellStateType::Blocked->value => $symbol === null
                ? CellState::blocked()
                : throw new SerializationFailed($path . '.symbol must be null for a blocked cell.'),
            CellStateType::Filled->value => CellState::filled(
                new CellSymbol(self::string($symbol, $path . '.symbol')),
            ),
            default => throw new SerializationFailed($path . '.state has an unknown value.'),
        };
    }

    private static function deserializeEntry(mixed $value, string $path, ResourceLimits $limits): CrosswordEntry
    {
        $data = self::object($value, $path);
        self::exactKeys($data, ['answer', 'senseKey', 'placement'], $path);

        $answerData = self::object($data['answer'] ?? null, $path . '.answer');
        self::exactKeys($answerData, ['key', 'displayText', 'cells'], $path . '.answer');
        $symbolData = self::list($answerData['cells'] ?? null, $path . '.answer.cells');
        if ($symbolData === []) {
            throw new SerializationFailed($path . '.answer.cells cannot be empty.');
        }
        $limits->assertEntryLength(\count($symbolData));
        $symbols = [];
        foreach ($symbolData as $index => $symbol) {
            $symbols[] = new CellSymbol(self::string($symbol, \sprintf('%s.answer.cells[%d]', $path, $index)));
        }
        $answer = new Answer(
            new AnswerKey(self::string($answerData['key'] ?? null, $path . '.answer.key')),
            $symbols,
            self::string($answerData['displayText'] ?? null, $path . '.answer.displayText'),
            $limits,
        );

        $placementData = self::object($data['placement'] ?? null, $path . '.placement');
        self::exactKeys($placementData, ['row', 'column', 'direction', 'length'], $path . '.placement');
        $directionValue = self::string($placementData['direction'] ?? null, $path . '.placement.direction');
        $direction = Direction::tryFrom($directionValue)
            ?? throw new SerializationFailed($path . '.placement.direction has an unknown value.');
        $placement = new Placement(
            new Position(
                self::integer($placementData['row'] ?? null, $path . '.placement.row'),
                self::integer($placementData['column'] ?? null, $path . '.placement.column'),
            ),
            $direction,
            self::integer($placementData['length'] ?? null, $path . '.placement.length'),
            $limits,
        );

        $senseValue = $data['senseKey'] ?? null;

        return new CrosswordEntry(
            $answer,
            $placement,
            $senseValue === null ? null : new SenseKey(self::string($senseValue, $path . '.senseKey')),
        );
    }

    /** @return array<string, mixed> */
    private static function object(mixed $value, string $path): array
    {
        if (!\is_array($value) || array_is_list($value)) {
            throw new SerializationFailed($path . ' must be an object.');
        }

        $object = [];
        foreach ($value as $key => $item) {
            if (!\is_string($key)) {
                throw new SerializationFailed($path . ' must use string field names.');
            }
            $object[$key] = $item;
        }

        return $object;
    }

    /** @return list<mixed> */
    private static function list(mixed $value, string $path): array
    {
        if (!\is_array($value) || !array_is_list($value)) {
            throw new SerializationFailed($path . ' must be a list.');
        }

        return $value;
    }

    private static function string(mixed $value, string $path): string
    {
        if (!\is_string($value)) {
            throw new SerializationFailed($path . ' must be a string.');
        }

        return $value;
    }

    private static function integer(mixed $value, string $path): int
    {
        if (!\is_int($value)) {
            throw new SerializationFailed($path . ' must be an integer.');
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $value
     * @param list<string> $keys
     */
    private static function exactKeys(array $value, array $keys, string $path): void
    {
        $actual = array_keys($value);
        sort($actual);
        sort($keys);
        if ($actual !== $keys) {
            throw new SerializationFailed($path . ' contains missing or unknown fields.');
        }
    }
}
