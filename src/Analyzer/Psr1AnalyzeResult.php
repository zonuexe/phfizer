<?php

declare(strict_types=1);

namespace zonuexe\Phfizer\Analyzer;

readonly class Psr1AnalyzeResult
{
    /** @param list<Psr1Violation> $violations */
    public function __construct(
        public string $name,
        public string $path,
        public array $violations,
    ) {
    }
}
