<?php

declare(strict_types=1);

namespace zonuexe\Phfizer\Cache;

use function rtrim;
use function sprintf;

/**
 * Builds a {@see ResultCache} for a run, applying the `--cache-dir` / `--no-cache`
 * options on top of the configured default cache directory.
 */
final class ResultCacheFactory
{
    public function __construct(
        private string $defaultCacheDir,
    ) {
    }

    public function create(?string $cacheDir = null, bool $enabled = true): ResultCache
    {
        if (!$enabled) {
            return new NullResultCache();
        }

        $dir = rtrim($cacheDir ?? $this->defaultCacheDir, '/');

        return new FileResultCache(sprintf('%s/psr1-results.json', $dir));
    }
}
