<?php

declare(strict_types=1);

namespace zonuexe\Phfizer\Cache;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use zonuexe\Phfizer\Analyzer\Psr1Violation;
use function dirname;
use function is_dir;
use function is_file;
use function rmdir;
use function sprintf;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

#[CoversClass(FileResultCache::class)]
final class FileResultCacheTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        $this->file = sprintf('%s/phfizer-test-%s/cache.json', sys_get_temp_dir(), uniqid('', true));
    }

    protected function tearDown(): void
    {
        if (is_file($this->file)) {
            unlink($this->file);
        }

        $dir = dirname($this->file);
        if (is_dir($dir)) {
            rmdir($dir);
        }
    }

    public function testReturnsNullOnMiss(): void
    {
        $cache = new FileResultCache($this->file);

        self::assertNull($cache->get('deadbeef'));
    }

    public function testPersistsAndReloadsAcrossInstances(): void
    {
        $cache = new FileResultCache($this->file);
        $cache->set('with-violation', [Psr1Violation::MIXED_SIDE_EFFECTS]);
        $cache->set('clean', []);
        $cache->save();

        $reloaded = new FileResultCache($this->file);

        self::assertSame([Psr1Violation::MIXED_SIDE_EFFECTS], $reloaded->get('with-violation'));
        // A clean result is an empty list, which must be distinguishable from a miss (null).
        self::assertSame([], $reloaded->get('clean'));
        self::assertNull($reloaded->get('never-seen'));
    }

    public function testDoesNotWriteFileWhenNothingChanged(): void
    {
        $cache = new FileResultCache($this->file);
        $cache->save();

        self::assertFileDoesNotExist($this->file);
    }
}
