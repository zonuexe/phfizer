<?php

declare(strict_types=1);

namespace zonuexe\Phfizer\Cache;

use zonuexe\Phfizer\Analyzer\Psr1Violation;
use function array_map;
use function dirname;
use function file_get_contents;
use function file_put_contents;
use function is_array;
use function is_dir;
use function is_file;
use function is_string;
use function json_decode;
use function json_encode;
use function mkdir;
use const LOCK_EX;

/**
 * Stores analysis results in a single JSON file, loaded once on construction and
 * flushed once via {@see save()}. The whole file is namespaced by a format
 * version so that incompatible cache files are silently discarded.
 */
final class FileResultCache implements ResultCache
{
    /**
     * Bump whenever the cached payload format or the analyzer semantics change so
     * that stale results produced by an older version are ignored.
     */
    private const VERSION = 1;

    /** @var array<string, list<Psr1Violation>> */
    private array $entries;

    private bool $dirty = false;

    public function __construct(
        private string $file,
    ) {
        $this->entries = $this->load();
    }

    public function get(string $hash): ?array
    {
        return $this->entries[$hash] ?? null;
    }

    public function set(string $hash, array $violations): void
    {
        $this->entries[$hash] = $violations;
        $this->dirty = true;
    }

    public function save(): void
    {
        if (!$this->dirty) {
            return;
        }

        $results = array_map(
            static fn (array $violations): array
                => array_map(static fn (Psr1Violation $violation): string => $violation->name, $violations),
            $this->entries,
        );

        $json = json_encode([
            'version' => self::VERSION,
            'results' => $results,
        ]);
        if ($json === false) {
            return;
        }

        $dir = dirname($this->file);
        if (!is_dir($dir)) {
            mkdir($dir, 0o777, true);
        }

        file_put_contents($this->file, $json, LOCK_EX);
        $this->dirty = false;
    }

    /** @return array<string, list<Psr1Violation>> */
    private function load(): array
    {
        if (!is_file($this->file)) {
            return [];
        }

        $json = file_get_contents($this->file);
        if ($json === false) {
            return [];
        }

        $data = json_decode($json, true);
        if (!is_array($data) || ($data['version'] ?? null) !== self::VERSION) {
            return [];
        }

        $results = $data['results'] ?? null;
        if (!is_array($results)) {
            return [];
        }

        $entries = [];
        foreach ($results as $hash => $names) {
            if (!is_string($hash) || !is_array($names)) {
                continue;
            }

            $violations = [];
            foreach ($names as $name) {
                $violation = is_string($name) ? Psr1Violation::tryFromName($name) : null;
                if ($violation !== null) {
                    $violations[] = $violation;
                }
            }

            $entries[$hash] = $violations;
        }

        return $entries;
    }
}
