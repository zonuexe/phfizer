<?php

declare(strict_types=1);

namespace zonuexe\Phfizer\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StreamableInputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Finder\Finder;
use zonuexe\Phfizer\Analyzer\FileAnalysisResult;
use zonuexe\Phfizer\Analyzer\FileAnalyzer;
use zonuexe\Phfizer\Cache\ResultCache;
use zonuexe\Phfizer\Cache\ResultCacheFactory;
use zonuexe\Phfizer\Parallel\CpuCoreCountProvider;
use zonuexe\Phfizer\Parallel\ParallelFileProcessor;
use zonuexe\Phfizer\Parallel\WorkerCommandLineFactory;
use zonuexe\Phfizer\Printer\Psr1TsvPrinter;
use function array_combine;
use function array_filter;
use function array_map;
use function count;
use function explode;
use function fopen;
use function is_numeric;
use function is_string;
use function realpath;
use function stream_get_contents;
use const DIRECTORY_SEPARATOR;

class AnalyzeCommand extends Command
{
    public function __construct(
        private FileAnalyzer $fileAnalyzer,
        private ResultCacheFactory $cacheFactory,
        private CpuCoreCountProvider $cpuCoreCountProvider,
        private WorkerCommandLineFactory $workerCommandLineFactory,
        private ParallelFileProcessor $parallelFileProcessor,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('analyze')
            ->setDescription('Analyze PHP files')
            ->setDefinition([
                new InputArgument('paths', InputArgument::IS_ARRAY, 'Paths to analyze'),
                new InputOption('output', 'o', InputOption::VALUE_OPTIONAL, 'Path to output'),
                new InputOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format', 'tsv'),
                new InputOption('jobs', 'j', InputOption::VALUE_REQUIRED, 'Number of parallel jobs (0 = auto-detect CPU threads)', '0'),
                new InputOption('no-parallel', null, InputOption::VALUE_NONE, 'Disable parallel processing'),
                new InputOption('cache-dir', null, InputOption::VALUE_REQUIRED, 'Directory to store the analysis result cache'),
                new InputOption('no-cache', null, InputOption::VALUE_NONE, 'Disable the analysis result cache'),
            ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var list<string> */
        $paths = $input->getArgument('paths');
        $outFile = $input->getOption('output');
        $errOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

        if ($outFile !== null) {
            $fp = fopen($outFile, 'w');
            if ($fp === false) {
                $errOutput->writeln('Failed to open output file');
                return 1;
            }
            $output = new StreamOutput($fp);
        }

        $inputStream = $input instanceof StreamableInputInterface ? $input->getStream() : null;
        if ($inputStream !== null) {
            $inputLines = array_filter(explode("\n", stream_get_contents($inputStream) ?: ''));
        } else {
            $inputLines = [];
        }
        foreach ($paths as $dir) {
            foreach ((new Finder())->files()->in($dir)->name('*.php') as $file) {
                $inputLines[] = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file->getRelativePathname();
            }
        }

        $cacheDirOption = $input->getOption('cache-dir');
        $cacheDir = is_string($cacheDirOption) ? $cacheDirOption : null;
        $cacheEnabled = !$input->getOption('no-cache');

        $files = array_combine($inputLines, array_map(realpath(...), $inputLines));

        // Only existing files are analyzed; missing ones are skipped silently, as before.
        $validFiles = [];
        foreach ($files as $name => $realpath) {
            if ($realpath !== false) {
                $validFiles[$name] = $realpath;
            }
        }

        $jobsOption = $input->getOption('jobs');
        $requestedJobs = is_numeric($jobsOption) ? (int) $jobsOption : 0;
        $jobs = $requestedJobs > 0 ? $requestedJobs : $this->cpuCoreCountProvider->provide();

        $useParallel = !$input->getOption('no-parallel') && $jobs > 1 && count($validFiles) > 1;

        if ($useParallel) {
            $fileResults = $this->parallelFileProcessor->process(
                $validFiles,
                $jobs,
                $this->workerCommandLineFactory->create($cacheDir, $cacheEnabled),
            );
            $this->persist($this->cacheFactory->create($cacheDir, $cacheEnabled), $fileResults);
        } else {
            $cache = $this->cacheFactory->create($cacheDir, $cacheEnabled);
            $fileResults = [];
            foreach ($validFiles as $name => $realpath) {
                $result = $this->fileAnalyzer->analyze($name, $realpath, $cache);
                if ($result !== null) {
                    $fileResults[] = $result;
                }
            }
            $this->persist($cache, $fileResults);
        }

        $psr1results = array_map(static fn (FileAnalysisResult $result) => $result->toAnalyzeResult(), $fileResults);

        $printer = new Psr1TsvPrinter();
        $printer->print($output, $psr1results);

        return 0;
    }

    /** @param list<FileAnalysisResult> $fileResults */
    private function persist(ResultCache $cache, array $fileResults): void
    {
        foreach ($fileResults as $result) {
            $cache->set($result->hash, $result->violations);
        }

        $cache->save();
    }
}
