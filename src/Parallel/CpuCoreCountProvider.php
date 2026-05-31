<?php

declare(strict_types=1);

namespace zonuexe\Phfizer\Parallel;

use function function_exists;
use function implode;
use function is_string;
use function shell_exec;
use function sprintf;
use function trim;

/**
 * Detects the number of available CPU threads, which bounds how many workers
 * run concurrently. Falls back to a single thread when detection fails.
 */
final class CpuCoreCountProvider
{
    /**
     * Maps the binary to probe (key) to its arguments (value). The first binary
     * that exists and returns a positive integer wins. `nproc` covers Linux,
     * `sysctl` covers macOS/BSD.
     */
    private const COMMANDS = [
        'nproc' => [],
        'sysctl' => ['-n', 'hw.ncpu'],
    ];

    public function provide(): int
    {
        if (!function_exists('shell_exec')) {
            return 1;
        }

        foreach (self::COMMANDS as $binary => $arguments) {
            if (!$this->isAvailable($binary)) {
                continue;
            }

            $output = shell_exec(implode(' ', [$binary, ...$arguments]));
            if (!is_string($output)) {
                continue;
            }

            $count = (int) trim($output);
            if ($count > 0) {
                return $count;
            }
        }

        return 1;
    }

    private function isAvailable(string $binary): bool
    {
        $found = shell_exec(sprintf('command -v %s 2>/dev/null', $binary));

        return is_string($found) && trim($found) !== '';
    }
}
