<?php

declare(strict_types=1);

namespace zonuexe\Phfizer\Analyzer;

use InvalidArgumentException;
use JsonSerializable;
use function array_map;
use function is_array;
use function is_string;

/**
 * Result of analyzing a single file, used both as the in-process result and as
 * the wire format exchanged with parallel worker subprocesses (ND-JSON).
 */
final readonly class FileAnalysisResult implements JsonSerializable
{
    /**
     * @param non-empty-string $hash
     * @param list<Psr1Violation> $violations
     */
    public function __construct(
        public string $name,
        public string $path,
        public string $hash,
        public array $violations,
    ) {
    }

    public function toAnalyzeResult(): Psr1AnalyzeResult
    {
        return new Psr1AnalyzeResult($this->name, $this->path, $this->violations);
    }

    /** @return array{name: string, path: string, hash: string, violations: list<string>} */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'path' => $this->path,
            'hash' => $this->hash,
            'violations' => array_map(static fn (Psr1Violation $violation): string => $violation->name, $this->violations),
        ];
    }

    /** @param array<array-key, mixed> $data */
    public static function fromArray(array $data): self
    {
        $name = $data['name'] ?? null;
        $path = $data['path'] ?? null;
        $hash = $data['hash'] ?? null;
        $violations = $data['violations'] ?? null;

        if (!is_string($name) || !is_string($path) || !is_string($hash) || $hash === '' || !is_array($violations)) {
            throw new InvalidArgumentException('Malformed worker result.');
        }

        $cases = [];
        foreach ($violations as $violation) {
            $case = is_string($violation) ? Psr1Violation::tryFromName($violation) : null;
            if ($case !== null) {
                $cases[] = $case;
            }
        }

        return new self($name, $path, $hash, $cases);
    }
}
