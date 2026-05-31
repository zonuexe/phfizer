<?php

declare(strict_types=1);

namespace zonuexe\Phfizer\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use zonuexe\Phfizer\Analyzer\FileAnalyzer;
use zonuexe\Phfizer\Cache\ResultCacheFactory;
use function explode;
use function file_get_contents;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;

/**
 * Internal command spawned by {@see AnalyzeCommand} for parallel runs. It reads a
 * batch of {@code {"name":..,"path":..}} objects (ND-JSON) from STDIN, analyzes
 * each file, and writes one ND-JSON {@see \zonuexe\Phfizer\Analyzer\FileAnalysisResult}
 * per line to STDOUT. The cache is only read here; the parent process owns writes.
 */
class WorkerCommand extends Command
{
    public const NAME = 'worker';

    public function __construct(
        private FileAnalyzer $fileAnalyzer,
        private ResultCacheFactory $cacheFactory,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName(self::NAME)
            ->setDescription('Internal worker: analyze a batch of files passed as ND-JSON on STDIN')
            ->setHidden(true)
            ->setDefinition([
                new InputOption('cache-dir', null, InputOption::VALUE_REQUIRED, 'Directory to store the analysis result cache'),
                new InputOption('no-cache', null, InputOption::VALUE_NONE, 'Disable the analysis result cache'),
            ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cacheDirOption = $input->getOption('cache-dir');
        $cache = $this->cacheFactory->create(
            is_string($cacheDirOption) ? $cacheDirOption : null,
            !$input->getOption('no-cache'),
        );

        $stdin = file_get_contents('php://stdin') ?: '';
        foreach (explode("\n", $stdin) as $line) {
            if ($line === '') {
                continue;
            }

            $data = json_decode($line, true);
            if (!is_array($data)) {
                continue;
            }

            $name = $data['name'] ?? null;
            $path = $data['path'] ?? null;
            if (!is_string($name) || !is_string($path)) {
                continue;
            }

            $result = $this->fileAnalyzer->analyze($name, $path, $cache);
            if ($result === null) {
                continue;
            }

            $json = json_encode($result);
            if ($json !== false) {
                $output->writeln($json, OutputInterface::OUTPUT_RAW);
            }
        }

        return 0;
    }
}
