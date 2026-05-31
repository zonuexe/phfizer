<?php

declare(strict_types=1);

namespace zonuexe\Phfizer\Analyzer;

use PhpParser\Parser;
use zonuexe\Phfizer\Cache\ResultCache;
use function file_get_contents;
use function hash;

/**
 * Analyzes a single file's contents. Shared by the sequential run and by the
 * parallel worker so both behave identically.
 */
final readonly class FileAnalyzer
{
    public function __construct(
        private Parser $parser,
        private Psr1Analyzer $psr1Analyzer,
    ) {
    }

    /** @return FileAnalysisResult|null null when the file cannot be parsed (syntax error) */
    public function analyze(string $name, string $path, ResultCache $cache): ?FileAnalysisResult
    {
        $contents = file_get_contents($path) ?: '';
        $hash = hash('xxh128', $contents);

        $cached = $cache->get($hash);
        if ($cached !== null) {
            return new FileAnalysisResult($name, $path, $hash, $cached);
        }

        $ast = $this->parser->parse($contents);
        if ($ast === null) {
            return null;
        }

        $result = $this->psr1Analyzer->analyze($name, $path, $ast);

        return new FileAnalysisResult($name, $path, $hash, $result->violations);
    }
}
