<?php

declare(strict_types=1);

namespace zonuexe\Phfizer\Parallel;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use zonuexe\Phfizer\Analyzer\FileAnalysisResult;
use function array_chunk;
use function ceil;
use function count;
use function explode;
use function is_array;
use function json_decode;
use function json_encode;
use function max;

/**
 * Splits the files into one batch per job and runs each batch in a worker
 * subprocess concurrently, then collects the ND-JSON results they emit.
 */
final readonly class ParallelFileProcessor
{
    /**
     * @param array<string, string> $files name => path
     * @param list<string> $workerCommand command line that runs the worker
     * @return list<FileAnalysisResult>
     */
    public function process(array $files, int $jobs, array $workerCommand): array
    {
        $jobs = max(1, $jobs);

        $pairs = [];
        foreach ($files as $name => $path) {
            $pairs[] = [
                'name' => $name,
                'path' => $path,
            ];
        }

        $chunkSize = max(1, (int) ceil(count($pairs) / $jobs));
        $chunks = array_chunk($pairs, $chunkSize);

        /** @var list<Process> $processes */
        $processes = [];
        foreach ($chunks as $chunk) {
            $process = new Process($workerCommand);
            $process->setTimeout(null);
            $process->setInput($this->encodeInput($chunk));
            $process->start();
            $processes[] = $process;
        }

        $results = [];
        foreach ($processes as $process) {
            $process->wait();
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            foreach (explode("\n", $process->getOutput()) as $line) {
                if ($line === '') {
                    continue;
                }

                $data = json_decode($line, true);
                if (is_array($data)) {
                    $results[] = FileAnalysisResult::fromArray($data);
                }
            }
        }

        return $results;
    }

    /** @param list<array{name: string, path: string}> $chunk */
    private function encodeInput(array $chunk): string
    {
        $input = '';
        foreach ($chunk as $pair) {
            $json = json_encode($pair);
            if ($json !== false) {
                $input .= $json . "\n";
            }
        }

        return $input;
    }
}
