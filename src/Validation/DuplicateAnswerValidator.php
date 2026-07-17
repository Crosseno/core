<?php

declare(strict_types=1);

namespace Crosseno\Core\Validation;

use Crosseno\Core\Crossword\Crossword;

final readonly class DuplicateAnswerValidator implements Validator
{
    public function validate(Crossword $crossword, ValidationProfile $profile): array
    {
        if ($profile->duplicateAnswers === DuplicateAnswerPolicy::Allow) {
            return [];
        }

        $seen = [];
        $violations = [];
        foreach ($crossword->entries() as $index => $entry) {
            $key = $entry->answer->key->value;
            if (isset($seen[$key])) {
                $violations[] = new Violation(
                    ViolationCode::DuplicateAnswer,
                    'Answer key is used by more than one entry.',
                    \sprintf('entries[%d].answer.key', $index),
                    ['answerKey' => $key, 'firstEntry' => $seen[$key]],
                );
            } else {
                $seen[$key] = $index;
            }
        }

        return $violations;
    }
}
