<?php

declare(strict_types=1);

namespace Crosseno\Core\Validation;

use Crosseno\Core\Exception\InvalidDomainValue;

final readonly class ValidationProfile
{
    public function __construct(
        public int $minimumEntryLength,
        public ConnectivityPolicy $connectivity,
        public DuplicateAnswerPolicy $duplicateAnswers,
        public UncheckedCellPolicy $uncheckedCells,
        public SymmetryPolicy $symmetry,
        public AdjacencyPolicy $adjacency,
    ) {
        if ($minimumEntryLength < 1) {
            throw new InvalidDomainValue('Minimum entry length must be positive.');
        }
    }

    public static function permissive(): self
    {
        return new self(
            1,
            ConnectivityPolicy::Ignore,
            DuplicateAnswerPolicy::Allow,
            UncheckedCellPolicy::Allow,
            SymmetryPolicy::None,
            AdjacencyPolicy::Allow,
        );
    }
}
