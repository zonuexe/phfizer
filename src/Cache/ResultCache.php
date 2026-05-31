<?php

declare(strict_types=1);

namespace zonuexe\Phfizer\Cache;

use zonuexe\Phfizer\Analyzer\Psr1Violation;

/**
 * Caches the analysis result of a single file keyed by the hash of its contents.
 *
 * The key is derived purely from the file contents, so identical files (even when
 * renamed or located elsewhere) share a cache entry, and any change to a file
 * invalidates only that entry.
 */
interface ResultCache
{
    /**
     * @param non-empty-string $hash content hash of the analyzed file
     * @return list<Psr1Violation>|null cached violations, or null on a cache miss
     */
    public function get(string $hash): ?array;

    /**
     * @param non-empty-string $hash content hash of the analyzed file
     * @param list<Psr1Violation> $violations
     */
    public function set(string $hash, array $violations): void;

    /** Persist any entries written since the cache was loaded. */
    public function save(): void;
}
