<?php

declare(strict_types=1);

namespace zonuexe\Phfizer\Parallel;

use zonuexe\Phfizer\Command\WorkerCommand;
use const PHP_BINARY;

/**
 * Builds the command line used to spawn a {@see WorkerCommand} subprocess,
 * re-using the PHP binary and entry script of the current run.
 */
final readonly class WorkerCommandLineFactory
{
    public function __construct(
        private string $mainScript,
    ) {
    }

    /** @return list<string> */
    public function create(?string $cacheDir, bool $cacheEnabled): array
    {
        $command = [PHP_BINARY, $this->mainScript, WorkerCommand::NAME];

        if (!$cacheEnabled) {
            $command[] = '--no-cache';
        } elseif ($cacheDir !== null) {
            $command[] = '--cache-dir=' . $cacheDir;
        }

        return $command;
    }
}
