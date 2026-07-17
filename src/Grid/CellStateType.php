<?php

declare(strict_types=1);

namespace Crosseno\Core\Grid;

enum CellStateType: string
{
    case Empty = 'empty';
    case Blocked = 'blocked';
    case Filled = 'filled';
}
