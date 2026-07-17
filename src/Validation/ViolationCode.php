<?php

declare(strict_types=1);

namespace Crosseno\Core\Validation;

enum ViolationCode: string
{
    case EntryTooShort = 'entry_too_short';
    case DisconnectedEntry = 'disconnected_entry';
    case DuplicateAnswer = 'duplicate_answer';
    case UncheckedCell = 'unchecked_cell';
    case SymmetryMismatch = 'symmetry_mismatch';
    case AdjacentEntries = 'adjacent_entries';
}
