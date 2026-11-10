<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * src/ supports PHP 8.1, but PHPStan and php -l on a newer PHP accept PHP 8.4's
 * new Foo()->bar(), which 8.1 to 8.3 can't parse. This scans the tokens to catch it.
 */
final class SourceSyntaxTest extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function sourceFiles(): iterable
    {
        foreach (glob(__DIR__ . '/../src/*.php') ?: [] as $file) {
            yield basename($file) => [$file];
        }
    }

    #[DataProvider('sourceFiles')]
    public function testNoCallOnABareNewExpression(string $file): void
    {
        $code = file_get_contents($file);
        self::assertIsString($code);

        $tokens = array_values(array_filter(
            token_get_all($code),
            static fn(array|string $token): bool => !is_array($token) || !in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true),
        ));

        $problems = [];
        foreach ($tokens as $index => $token) {
            if (!is_array($token) || $token[0] !== T_NEW) {
                continue;
            }

            if (($tokens[$index - 1] ?? null) === '(') {
                continue;
            }

            $end = self::endOfNewExpression($tokens, $index);
            $next = $tokens[$end + 1] ?? null;
            if ($next !== null && (in_array($next, ['[', '('], true) || (is_array($next) && in_array($next[0], [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON], true)))) {
                $problems[] = sprintf('line %d', $token[2]);
            }
        }

        self::assertSame([], $problems, basename($file) . ' calls on a bare new expression, which needs PHP 8.4');
    }

    /**
     * The index of the last token of the new expression starting at $new: the closing parenthesis
     * of its arguments, or the class name when it has none.
     *
     * @param list<array{int, string, int}|string> $tokens
     */
    private static function endOfNewExpression(array $tokens, int $new): int
    {
        $index = $new + 1;
        while (isset($tokens[$index + 1]) && $tokens[$index + 1] !== '(' && $tokens[$index + 1] !== ';' && $tokens[$index + 1] !== ',' && $tokens[$index + 1] !== ')') {
            $index++;
        }

        if (($tokens[$index + 1] ?? null) !== '(') {
            return $index;
        }

        $depth = 0;
        for ($index++; isset($tokens[$index]); $index++) {
            if ($tokens[$index] === '(') {
                $depth++;
            } elseif ($tokens[$index] === ')' && --$depth === 0) {
                return $index;
            }
        }

        return $index;
    }
}
