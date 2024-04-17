<?php

declare(strict_types=1);

namespace zonuexe\Phfizer\Printer;

use Symfony\Component\Console\Output\OutputInterface;
use zonuexe\Phfizer\Analyzer\Psr1AnalyzeResult;
use function array_map;
use function fopen;
use function fputcsv;
use function implode;
use function rewind;
use function stream_get_contents;

readonly class Psr1TsvPrinter
{
    public function __construct()
    {
    }

    /** @param list<Psr1AnalyzeResult> $psr1Results */
    public function print(OutputInterface $output, array $psr1Results): void
    {
        $fp = fopen('php://temp', 'w+');
        assert($fp !== false);
        foreach ($psr1Results as $result) {
            $row = [
                $result->name,
                implode(',', array_map(static fn ($violation) => $violation->name, $result->violations)),
            ];
            fputcsv($fp, $row, "\t");
        }

        rewind($fp);

        $output->write(stream_get_contents($fp) ?: '');
    }
}
