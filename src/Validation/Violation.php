<?php

declare(strict_types=1);

namespace Crosseno\Core\Validation;

final readonly class Violation
{
    /**
     * @param array<string, bool|int|float|string|null> $context
     */
    public function __construct(
        public ViolationCode $code,
        public string $message,
        public string $path,
        public array $context = [],
    ) {}
}
