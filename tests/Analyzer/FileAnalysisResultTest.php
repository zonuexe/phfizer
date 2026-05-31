<?php

declare(strict_types=1);

namespace zonuexe\Phfizer\Analyzer;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use function is_array;
use function json_decode;
use function json_encode;

#[CoversClass(FileAnalysisResult::class)]
final class FileAnalysisResultTest extends TestCase
{
    public function testSurvivesJsonRoundTrip(): void
    {
        $result = new FileAnalysisResult('a.php', '/abs/a.php', 'abcd1234', [Psr1Violation::MIXED_SIDE_EFFECTS]);

        $json = json_encode($result);
        if ($json === false) {
            self::fail('Failed to encode result.');
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            self::fail('Decoded value is not an array.');
        }

        $restored = FileAnalysisResult::fromArray($decoded);

        self::assertSame('a.php', $restored->name);
        self::assertSame('/abs/a.php', $restored->path);
        self::assertSame('abcd1234', $restored->hash);
        self::assertSame([Psr1Violation::MIXED_SIDE_EFFECTS], $restored->violations);
    }

    public function testConvertsToAnalyzeResult(): void
    {
        $analyzeResult = (new FileAnalysisResult('a.php', '/abs/a.php', 'abcd1234', []))->toAnalyzeResult();

        self::assertSame('a.php', $analyzeResult->name);
        self::assertSame('/abs/a.php', $analyzeResult->path);
        self::assertSame([], $analyzeResult->violations);
    }

    public function testFromArrayRejectsMalformedPayload(): void
    {
        $this->expectException(InvalidArgumentException::class);

        FileAnalysisResult::fromArray([
            'name' => 'a.php',
        ]);
    }
}
