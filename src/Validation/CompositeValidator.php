<?php

declare(strict_types=1);

namespace Crosseno\Core\Validation;

use Crosseno\Core\Crossword\Crossword;
use Crosseno\Core\Exception\InvalidDomainValue;

final readonly class CompositeValidator implements Validator
{
    /** @var list<Validator> */
    private array $validators;

    /** @param list<Validator> $validators */
    public function __construct(array $validators)
    {
        if (!array_is_list($validators)) {
            throw new InvalidDomainValue('Validators must be provided as a list.');
        }
        foreach ($validators as $validator) {
            if (!$validator instanceof Validator) {
                throw new InvalidDomainValue('Every validator must implement Validator.');
            }
        }
        $this->validators = $validators;
    }

    public static function standard(): self
    {
        return new self([
            new EntryLengthValidator(),
            new ConnectivityValidator(),
            new DuplicateAnswerValidator(),
            new UncheckedCellValidator(),
            new SymmetryValidator(),
            new AdjacencyValidator(),
        ]);
    }

    public function validate(Crossword $crossword, ValidationProfile $profile): array
    {
        $violations = [];
        foreach ($this->validators as $validator) {
            array_push($violations, ...$validator->validate($crossword, $profile));
        }

        return $violations;
    }
}
