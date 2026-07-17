<?php

declare(strict_types=1);

namespace Crosseno\Core\Validation;

use Crosseno\Core\Crossword\Crossword;

final readonly class ConnectivityValidator implements Validator
{
    public function validate(Crossword $crossword, ValidationProfile $profile): array
    {
        $entries = $crossword->entries();
        if ($profile->connectivity === ConnectivityPolicy::Ignore || \count($entries) < 2) {
            return [];
        }

        $positionsToEntries = [];
        foreach ($entries as $entryIndex => $entry) {
            foreach ($entry->placement->positions() as $position) {
                $positionsToEntries[$position->key()][] = $entryIndex;
            }
        }

        $visited = [0 => true];
        $queue = [0];
        while ($queue !== []) {
            $current = array_shift($queue);
            foreach ($entries[$current]->placement->positions() as $position) {
                foreach ($positionsToEntries[$position->key()] as $neighbor) {
                    if (!isset($visited[$neighbor])) {
                        $visited[$neighbor] = true;
                        $queue[] = $neighbor;
                    }
                }
            }
        }

        $violations = [];
        foreach (array_keys($entries) as $entryIndex) {
            if (!isset($visited[$entryIndex])) {
                $violations[] = new Violation(
                    ViolationCode::DisconnectedEntry,
                    'Entry is disconnected from the first entry component.',
                    \sprintf('entries[%d]', $entryIndex),
                    ['entry' => $entryIndex],
                );
            }
        }

        return $violations;
    }
}
