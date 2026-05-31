<?php

declare(strict_types=1);

namespace zonuexe\Phfizer\Parallel;

use function function_exists;
use function is_string;
use function shell_exec;
use function trim;

/**
 * Detects the number of available CPU threads, which bounds how many workers
 * run concurrently. Falls back to a single thread when detection fails.
 */
final class CpuCoreCountProvider
{
    public function provide(): int
    {
        if (!function_exists('shell_exec')) {
            return 1;
        }

        foreach (['nproc', 'sysctl -n hw.ncpu'] as $command) {
            $output = shell_exec($command);
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
}
