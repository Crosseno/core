<?php

declare(strict_types=1);

namespace Crosseno\Core\Validation;

use Crosseno\Core\Crossword\Crossword;

final readonly class EntryLengthValidator implements Validator
{
    public function validate(Crossword $crossword, ValidationProfile $profile): array
    {
        $violations = [];
        foreach ($crossword->entries() as $index => $entry) {
            if ($entry->answer->length() < $profile->minimumEntryLength) {
                $violations[] = new Violation(
                    ViolationCode::EntryTooShort,
                    'Entry is shorter than the configured minimum.',
                    \sprintf('entries[%d]', $index),
                    ['actual' => $entry->answer->length(), 'minimum' => $profile->minimumEntryLength],
                );
            }
        }

        return $violations;
    }
}
