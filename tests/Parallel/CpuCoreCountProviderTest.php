<?php

declare(strict_types=1);

namespace zonuexe\Phfizer\Parallel;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CpuCoreCountProvider::class)]
final class CpuCoreCountProviderTest extends TestCase
{
    public function testProvidesAtLeastOneThread(): void
    {
        self::assertGreaterThanOrEqual(1, (new CpuCoreCountProvider())->provide());
    }
}
