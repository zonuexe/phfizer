<?php

declare(strict_types=1);

namespace zonuexe\Phfizer\Parallel;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use const PHP_BINARY;

#[CoversClass(WorkerCommandLineFactory::class)]
final class WorkerCommandLineFactoryTest extends TestCase
{
    public function testAppendsCacheDirWhenEnabled(): void
    {
        $factory = new WorkerCommandLineFactory('/path/to/phfizer');

        self::assertSame(
            [PHP_BINARY, '/path/to/phfizer', 'worker', '--cache-dir=/tmp/cache'],
            $factory->create('/tmp/cache', true),
        );
    }

    public function testAppendsNoCacheWhenDisabled(): void
    {
        $factory = new WorkerCommandLineFactory('/path/to/phfizer');

        self::assertSame(
            [PHP_BINARY, '/path/to/phfizer', 'worker', '--no-cache'],
            $factory->create('/tmp/cache', false),
        );
    }

    public function testOmitsCacheDirWhenUsingDefault(): void
    {
        $factory = new WorkerCommandLineFactory('/path/to/phfizer');

        self::assertSame(
            [PHP_BINARY, '/path/to/phfizer', 'worker'],
            $factory->create(null, true),
        );
    }
}
