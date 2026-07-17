<?php

declare(strict_types=1);

namespace Crosseno\Core\Answer;

use Crosseno\Core\Exception\InvalidDomainValue;
use Crosseno\Core\Grid\CellSymbol;
use Crosseno\Core\ResourceLimits;

final readonly class Answer
{
    /** @var non-empty-list<CellSymbol> */
    private array $cells;

    /**
     * Core accepts language-defined cells as-is; it never tokenizes or normalizes text.
     *
     * @param non-empty-list<CellSymbol> $cells
     */
    public function __construct(
        public AnswerKey $key,
        array $cells,
        public string $displayText,
        ResourceLimits $limits,
    ) {
        if (!array_is_list($cells) || $cells === []) {
            throw new InvalidDomainValue('An answer must have an ordered, non-empty list of cells.');
        }

        foreach ($cells as $cell) {
            if (!$cell instanceof CellSymbol) {
                throw new InvalidDomainValue('Every answer cell must be a CellSymbol.');
            }
        }

        if ($displayText === '' || preg_match('//u', $displayText) !== 1) {
            throw new InvalidDomainValue('Display text must be non-empty valid UTF-8.');
        }

        $limits->assertEntryLength(\count($cells));
        $this->cells = $cells;
    }

    /** @return non-empty-list<CellSymbol> */
    public function cells(): array
    {
        return $this->cells;
    }

    public function length(): int
    {
        return \count($this->cells);
    }
}
