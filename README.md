# Crosseno Core

Language-independent crossword domain objects, immutable grids, structural validation, and deterministic core snapshots for PHP 8.5 and later. The package has no runtime dependency beyond PHP.

## Installation

```bash
composer require crosseno/core
```

## Constructing a crossword

Callers define resource ceilings and pass already-tokenized cells. Core never changes normalization or decides where cell boundaries belong.

```php
<?php

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

$limits = ResourceLimits::standard();
$dimensions = new GridDimensions(5, 5);
$grid = Grid::empty($dimensions, $limits);

$cat = new Answer(
    new AnswerKey('en:cat'),
    [new CellSymbol('C'), new CellSymbol('A'), new CellSymbol('T')],
    'cat',
    $limits,
);
$ear = new Answer(
    new AnswerKey('en:ear'),
    [new CellSymbol('E'), new CellSymbol('A'), new CellSymbol('R')],
    'ear',
    $limits,
);

$crossword = new Crossword(
    $grid,
    [
        new CrosswordEntry($cat, new Placement(new Position(1, 0), Direction::Horizontal, 3, $limits)),
        new CrosswordEntry($ear, new Placement(new Position(0, 1), Direction::Vertical, 3, $limits)),
    ],
    DuplicatePlacementPolicy::Forbid,
    $limits,
);
```

The two entries cross at `(1, 1)` with the exact symbol `A`. A blocked cell, an out-of-bounds entry, or a different code-point sequence at that crossing is rejected during construction.

## Validation

`ValidationProfile` makes every convention explicit: minimum entry length, connectivity, duplicate answers, unchecked cells, block symmetry, and orthogonal adjacency. Use `CompositeValidator::standard()` or compose individual `Validator` implementations.

## Serialization

```php
use Crosseno\Core\Serialization\DomainSnapshotDeserializer;
use Crosseno\Core\Serialization\DomainSnapshotSerializer;

$json = (new DomainSnapshotSerializer())->serialize($crossword, $limits);
$copy = (new DomainSnapshotDeserializer())->deserialize($json, $limits);
```

The payload is deterministic UTF-8 JSON named `crosseno-core-snapshot`, currently at version `1`. It contains only the grid, entries, stable answer/sense keys, placements, and duplicate-placement policy. The schema is published at `resources/schema/core-snapshot-v1.schema.json`.

## Non-goals

This package does not tokenize or normalize answers, generate grids, choose random values, access lexicons or databases, model clues or learning metadata, define publication formats, or integrate with a CMS. Those responsibilities belong to other Crosseno packages.
