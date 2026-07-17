<?php

declare(strict_types=1);

namespace Crosseno\Core\Tests\Unit;

use Crosseno\Core\Answer\Answer;
use Crosseno\Core\Answer\AnswerKey;
use Crosseno\Core\Answer\SenseKey;
use Crosseno\Core\Exception\InvalidDomainValue;
use Crosseno\Core\Exception\ResourceLimitExceeded;
use Crosseno\Core\Grid\CellSymbol;
use Crosseno\Core\ResourceLimits;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CellAndAnswerTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function unicodeSymbols(): iterable
    {
        yield 'Polish L stroke' => ['Ł'];
        yield 'Polish Z dot' => ['Ż'];
        yield 'composed E acute' => ['É'];
        yield 'Spanish N tilde' => ['Ñ'];
        yield 'decomposed E acute' => ["E\u{0301}"];
        yield 'multi-code-point cell' => ['CH'];
    }

    #[DataProvider('unicodeSymbols')]
    public function testSymbolsMayContainOneOrMoreUnicodeCodePoints(string $value): void
    {
        self::assertSame($value, (new CellSymbol($value))->value);
    }

    public function testCoreDoesNotNormalizeSymbols(): void
    {
        self::assertFalse((new CellSymbol('É'))->equals(new CellSymbol("E\u{0301}")));
    }

    /** @return iterable<string, array{string}> */
    public static function invalidSymbols(): iterable
    {
        yield 'empty' => [''];
        yield 'space' => ['A B'];
        yield 'newline' => ["A\nB"];
    }

    #[DataProvider('invalidSymbols')]
    public function testEmptyOrWhitespaceSymbolsAreRejected(string $value): void
    {
        $this->expectException(InvalidDomainValue::class);
        new CellSymbol($value);
    }

    public function testAnswerKeepsOrderedCellsAndIndependentDisplayText(): void
    {
        $limits = ResourceLimits::standard();
        $answer = new Answer(
            new AnswerKey('pl:lodz'),
            [new CellSymbol('Ł'), new CellSymbol('Ó'), new CellSymbol('DŹ')],
            'Łódź',
            $limits,
        );

        self::assertSame(['Ł', 'Ó', 'DŹ'], array_map(static fn(CellSymbol $cell): string => $cell->value, $answer->cells()));
        self::assertSame('Łódź', $answer->displayText);
    }

    public function testAnswerLengthLimitIsEnforced(): void
    {
        $limits = new ResourceLimits(5, 5, 25, 2, 25, 1_000);

        $this->expectException(ResourceLimitExceeded::class);
        new Answer(
            new AnswerKey('too-long'),
            [new CellSymbol('A'), new CellSymbol('B'), new CellSymbol('C')],
            'ABC',
            $limits,
        );
    }

    public function testStableKeysUseExactOpaqueValues(): void
    {
        self::assertTrue((new AnswerKey('en:answer-42'))->equals(new AnswerKey('en:answer-42')));
        self::assertTrue((new SenseKey('wn:00001740-n'))->equals(new SenseKey('wn:00001740-n')));
    }
}
