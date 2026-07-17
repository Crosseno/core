<?php

declare(strict_types=1);

namespace Crosseno\Core\Tests\Integration;

use Crosseno\Core\Answer\SenseKey;
use Crosseno\Core\Crossword\CrosswordEntry;
use Crosseno\Core\Exception\ResourceLimitExceeded;
use Crosseno\Core\Exception\SerializationFailed;
use Crosseno\Core\Exception\UnsupportedSchemaVersion;
use Crosseno\Core\Grid\Direction;
use Crosseno\Core\Grid\Placement;
use Crosseno\Core\Grid\Position;
use Crosseno\Core\ResourceLimits;
use Crosseno\Core\Serialization\DomainSnapshotDeserializer;
use Crosseno\Core\Serialization\DomainSnapshotSerializer;
use Crosseno\Core\Tests\Support\DomainFactory;
use PHPUnit\Framework\TestCase;

final class DomainSnapshotTest extends TestCase
{
    public function testSerializationIsDeterministicAndRoundTrips(): void
    {
        $limits = ResourceLimits::standard();
        $first = DomainFactory::entry('first', ['Ł', 'Ż'], 2, 0, Direction::Horizontal, $limits);
        $secondAnswer = DomainFactory::answer(
            'unicode-boundaries',
            ['É', "E\u{0301}", 'CH', 'Ñ'],
            $limits,
            'display text differs',
        );
        $second = new CrosswordEntry(
            $secondAnswer,
            new Placement(new Position(0, 3), Direction::Vertical, 4, $limits),
            new SenseKey('sense:unicode'),
        );

        $crosswordA = DomainFactory::crossword([$first, $second], $limits, rows: 5, columns: 5);
        $crosswordB = DomainFactory::crossword([$second, $first], $limits, rows: 5, columns: 5);
        $serializer = new DomainSnapshotSerializer();

        $json = $serializer->serialize($crosswordA, $limits);
        self::assertSame($json, $serializer->serialize($crosswordA, $limits));
        self::assertSame($json, $serializer->serialize($crosswordB, $limits));

        $copy = (new DomainSnapshotDeserializer())->deserialize($json, $limits);
        self::assertSame($json, $serializer->serialize($copy, $limits));
        self::assertSame('display text differs', $copy->entries()[0]->answer->displayText);
        self::assertSame(
            ['É', "E\u{0301}", 'CH', 'Ñ'],
            array_map(static fn($cell): string => $cell->value, $copy->entries()[0]->answer->cells()),
        );
    }

    public function testUnsupportedSchemaVersionIsRejected(): void
    {
        $this->expectException(UnsupportedSchemaVersion::class);
        (new DomainSnapshotDeserializer())->deserialize(
            '{"schema":"crosseno-core-snapshot","version":2,"duplicatePlacementPolicy":"forbid","grid":{"rows":1,"columns":1,"cells":[{"state":"empty","symbol":null}]},"entries":[]}',
            ResourceLimits::standard(),
        );
    }

    public function testUnknownFieldsAreRejected(): void
    {
        $this->expectException(SerializationFailed::class);
        (new DomainSnapshotDeserializer())->deserialize(
            '{"schema":"crosseno-core-snapshot","version":1,"duplicatePlacementPolicy":"forbid","grid":{"rows":1,"columns":1,"cells":[{"state":"empty","symbol":null}]},"entries":[],"clues":[]}',
            ResourceLimits::standard(),
        );
    }

    public function testDimensionsAreLimitedBeforeCellConstruction(): void
    {
        $limits = new ResourceLimits(2, 2, 4, 2, 4, 10_000);

        $this->expectException(ResourceLimitExceeded::class);
        (new DomainSnapshotDeserializer())->deserialize(
            '{"schema":"crosseno-core-snapshot","version":1,"duplicatePlacementPolicy":"forbid","grid":{"rows":999,"columns":999,"cells":[]},"entries":[]}',
            $limits,
        );
    }
}
