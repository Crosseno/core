<?php

declare(strict_types=1);

namespace Crosseno\Core\Serialization;

use Crosseno\Core\Exception\SerializationFailed;

final readonly class DuplicateJsonKeyGuard
{
    public static function assertNone(string $json): void
    {
        $length = \strlen($json);
        $nextObjectId = 0;
        $objectStack = [];
        $keys = [];

        for ($offset = 0; $offset < $length; ++$offset) {
            $character = $json[$offset];
            if ($character === '{') {
                $objectStack[] = ++$nextObjectId;
                $keys[$nextObjectId] = [];
                continue;
            }
            if ($character === '}') {
                array_pop($objectStack);
                continue;
            }
            if ($character !== '"') {
                continue;
            }

            $start = $offset++;
            while ($offset < $length) {
                if ($json[$offset] === '\\') {
                    $offset += 2;
                    continue;
                }
                if ($json[$offset] === '"') {
                    break;
                }
                ++$offset;
            }
            if ($offset >= $length) {
                return;
            }

            $lookahead = $offset + 1;
            while ($lookahead < $length && preg_match('/\s/u', $json[$lookahead]) === 1) {
                ++$lookahead;
            }
            if ($lookahead >= $length || $json[$lookahead] !== ':' || $objectStack === []) {
                continue;
            }

            try {
                $key = json_decode(substr($json, $start, $offset - $start + 1), true, 2, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                return;
            }
            if (!\is_string($key)) {
                continue;
            }
            $objectId = $objectStack[array_key_last($objectStack)];
            if (isset($keys[$objectId][$key])) {
                throw new SerializationFailed(\sprintf('JSON object contains duplicate key "%s".', $key));
            }
            $keys[$objectId][$key] = true;
        }
    }
}
