<?php

declare(strict_types=1);

namespace Crosseno\Core\Grid;

use Crosseno\Core\Exception\InvalidDomainValue;

final readonly class CellState
{
    private function __construct(public CellStateType $type, public ?CellSymbol $symbol)
    {
        if (($type === CellStateType::Filled) !== ($symbol instanceof CellSymbol)) {
            throw new InvalidDomainValue('Only a filled cell has a symbol.');
        }
    }

    public static function empty(): self
    {
        return new self(CellStateType::Empty, null);
    }

    public static function blocked(): self
    {
        return new self(CellStateType::Blocked, null);
    }

    public static function filled(CellSymbol $symbol): self
    {
        return new self(CellStateType::Filled, $symbol);
    }

    public function equals(self $other): bool
    {
        return $this->type === $other->type
            && ($this->symbol?->value === $other->symbol?->value);
    }
}
