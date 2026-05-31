<?php

declare(strict_types=1);

namespace zonuexe\Phfizer\Cache;

/**
 * A cache that never stores anything; used when caching is disabled (`--no-cache`).
 */
final class NullResultCache implements ResultCache
{
    public function get(string $hash): ?array
    {
        return null;
    }

    public function set(string $hash, array $violations): void
    {
    }

    public function save(): void
    {
    }
}
