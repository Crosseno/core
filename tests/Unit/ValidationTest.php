<?php

declare(strict_types=1);

namespace Crosseno\Core\Tests\Unit;

use Crosseno\Core\Crossword\Crossword;
use Crosseno\Core\Crossword\DuplicatePlacementPolicy;
use Crosseno\Core\Grid\CellState;
use Crosseno\Core\Grid\Direction;
use Crosseno\Core\Grid\GridBuilder;
use Crosseno\Core\Grid\GridDimensions;
use Crosseno\Core\Grid\Position;
use Crosseno\Core\ResourceLimits;
use Crosseno\Core\Tests\Support\DomainFactory;
use Crosseno\Core\Validation\AdjacencyPolicy;
use Crosseno\Core\Validation\AdjacencyValidator;
use Crosseno\Core\Validation\CompositeValidator;
use Crosseno\Core\Validation\ConnectivityPolicy;
use Crosseno\Core\Validation\ConnectivityValidator;
use Crosseno\Core\Validation\DuplicateAnswerPolicy;
use Crosseno\Core\Validation\DuplicateAnswerValidator;
use Crosseno\Core\Validation\SymmetryPolicy;
use Crosseno\Core\Validation\SymmetryValidator;
use Crosseno\Core\Validation\UncheckedCellPolicy;
use Crosseno\Core\Validation\UncheckedCellValidator;
use Crosseno\Core\Validation\ValidationProfile;
use Crosseno\Core\Validation\ViolationCode;
use PHPUnit\Framework\TestCase;

final class ValidationTest extends TestCase
{
    public function testDisconnectedEntriesProduceStructuredViolations(): void
    {
        $limits = ResourceLimits::standard();
        $crossword = DomainFactory::crossword([
            DomainFactory::entry('one', ['A', 'B'], 0, 0, Direction::Horizontal, $limits),
            DomainFactory::entry('two', ['C', 'D'], 3, 2, Direction::Horizontal, $limits),
        ], $limits);

        $violations = (new ConnectivityValidator())->validate($crossword, self::profile(connectivity: ConnectivityPolicy::RequireConnected));

        self::assertCount(1, $violations);
        self::assertSame(ViolationCode::DisconnectedEntry, $violations[0]->code);
        self::assertSame('entries[1]', $violations[0]->path);
    }

    public function testDuplicateAnswerPolicyUsesStableKeys(): void
    {
        $limits = ResourceLimits::standard();
        $crossword = DomainFactory::crossword([
            DomainFactory::entry('same', ['A', 'B'], 0, 0, Direction::Horizontal, $limits),
            DomainFactory::entry('same', ['A', 'B'], 2, 0, Direction::Horizontal, $limits),
        ], $limits);

        $violations = (new DuplicateAnswerValidator())->validate($crossword, self::profile(duplicates: DuplicateAnswerPolicy::Forbid));

        self::assertSame(ViolationCode::DuplicateAnswer, $violations[0]->code);
        self::assertSame('same', $violations[0]->context['answerKey']);
    }

    public function testUncheckedCellPolicyRequiresTwoEntryCoverage(): void
    {
        $limits = ResourceLimits::standard();
        $crossword = DomainFactory::crossword([
            DomainFactory::entry('one', ['A', 'B'], 0, 0, Direction::Horizontal, $limits),
        ], $limits);

        $violations = (new UncheckedCellValidator())->validate($crossword, self::profile(unchecked: UncheckedCellPolicy::Forbid));

        self::assertCount(2, $violations);
        self::assertSame(ViolationCode::UncheckedCell, $violations[0]->code);
    }

    public function testRotationalSymmetryChecksBlockedPatternOnly(): void
    {
        $limits = ResourceLimits::standard();
        $grid = (new GridBuilder(new GridDimensions(3, 3), $limits))
            ->set(new Position(0, 0), CellState::blocked())
            ->build();
        $crossword = new Crossword($grid, [], DuplicatePlacementPolicy::Forbid, $limits);

        $violations = (new SymmetryValidator())->validate($crossword, self::profile(symmetry: SymmetryPolicy::Rotational180));

        self::assertCount(1, $violations);
        self::assertSame(ViolationCode::SymmetryMismatch, $violations[0]->code);
    }

    public function testAdjacencyProfileRejectsParallelTouchingEntries(): void
    {
        $limits = ResourceLimits::standard();
        $crossword = DomainFactory::crossword([
            DomainFactory::entry('one', ['A', 'B'], 0, 0, Direction::Horizontal, $limits),
            DomainFactory::entry('two', ['C', 'D'], 1, 0, Direction::Horizontal, $limits),
        ], $limits);

        $violations = (new AdjacencyValidator())->validate(
            $crossword,
            self::profile(adjacency: AdjacencyPolicy::ForbidUnsharedOrthogonalTouching),
        );

        self::assertCount(2, $violations);
        self::assertSame(ViolationCode::AdjacentEntries, $violations[0]->code);
    }

    public function testCompositeValidatorUsesAllExplicitProfilePolicies(): void
    {
        $limits = ResourceLimits::standard();
        $crossword = DomainFactory::crossword([
            DomainFactory::entry('short', ['A'], 0, 0, Direction::Horizontal, $limits),
        ], $limits);
        $profile = new ValidationProfile(
            2,
            ConnectivityPolicy::RequireConnected,
            DuplicateAnswerPolicy::Forbid,
            UncheckedCellPolicy::Allow,
            SymmetryPolicy::None,
            AdjacencyPolicy::Allow,
        );

        $violations = CompositeValidator::standard()->validate($crossword, $profile);

        self::assertCount(1, $violations);
        self::assertSame(ViolationCode::EntryTooShort, $violations[0]->code);
    }

    private static function profile(
        ConnectivityPolicy $connectivity = ConnectivityPolicy::Ignore,
        DuplicateAnswerPolicy $duplicates = DuplicateAnswerPolicy::Allow,
        UncheckedCellPolicy $unchecked = UncheckedCellPolicy::Allow,
        SymmetryPolicy $symmetry = SymmetryPolicy::None,
        AdjacencyPolicy $adjacency = AdjacencyPolicy::Allow,
    ): ValidationProfile {
        return new ValidationProfile(1, $connectivity, $duplicates, $unchecked, $symmetry, $adjacency);
    }
}
