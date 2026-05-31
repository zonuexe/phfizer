<?php

declare(strict_types=1);

namespace zonuexe\Phfizer\Command;

use PhpParser\Parser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StreamableInputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Finder\Finder;
use zonuexe\Phfizer\Analyzer\Psr1Analyzer;
use zonuexe\Phfizer\Analyzer\Psr1AnalyzeResult;
use zonuexe\Phfizer\Cache\ResultCacheFactory;
use zonuexe\Phfizer\Printer\Psr1TsvPrinter;
use function array_combine;
use function array_filter;
use function array_map;
use function explode;
use function file_get_contents;
use function fopen;
use function hash;
use function is_string;
use function realpath;
use function stream_get_contents;
use const DIRECTORY_SEPARATOR;

class AnalyzeCommand extends Command
{
    public function __construct(
        private Parser $parser,
        private Psr1Analyzer $psr1Analyzer,
        private ResultCacheFactory $cacheFactory,
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
        $cache = $this->cacheFactory->create(
            is_string($cacheDirOption) ? $cacheDirOption : null,
            !$input->getOption('no-cache'),
        );

        $files = array_combine($inputLines, array_map(realpath(...), $inputLines));
        $result = [];
        $errors = [];
        $psr1results = [];

        foreach ($files as $name => $realpath) {
            if ($realpath === false) {
                $errors[$name] = 'File not found';
                continue;
            }

            $contents = file_get_contents($realpath) ?: '';
            $hash = hash('xxh128', $contents);

            $cached = $cache->get($hash);
            if ($cached !== null) {
                $psr1results[] = new Psr1AnalyzeResult($name, $realpath, $cached);
                continue;
            }

            $ast = $this->parser->parse($contents);

            if ($ast === null) {
                $errors[$name] = 'Syntax error';
                continue;
            }

            $psr1result = $this->psr1Analyzer->analyze($name, $realpath, $ast);
            $cache->set($hash, $psr1result->violations);
            $psr1results[] = $psr1result;
        }

        $cache->save();

        $printer = new Psr1TsvPrinter();
        $printer->print($output, $psr1results);

        return 0;
    }
}
