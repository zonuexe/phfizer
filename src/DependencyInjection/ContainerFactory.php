<?php

declare(strict_types=1);

namespace zonuexe\Phfizer\DependencyInjection;

use Illuminate\Container\Container;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use zonuexe\Phfizer\Command\AnalyzeCommand;
use function method_exists;

final class ContainerFactory
{
    public function create(): Container
    {
        $container = new Container();
        $container->singleton(SymfonyStyle::class, static function (): SymfonyStyle {
            $arrayInput = new ArrayInput([]);
            $consoleOutput = new ConsoleOutput();

            return new SymfonyStyle($arrayInput, $consoleOutput);
        });

        $container->singleton(Parser::class, static function () {
            return (new ParserFactory())->createForNewestSupportedVersion();
        });

        $container->singleton(Application::class, static function (Container $container) {
            $app = new Application(
                name: 'Phfizer - PHP File Analyzer',
                version: '0.0.1',
            );
            $app->setDefaultCommand('analyze');
            if (method_exists($app, 'add')) {
                $app->add($container->make(AnalyzeCommand::class));
            } else {
                $app->addCommand($container->make(AnalyzeCommand::class));
            }

            return $app;
        });

        return $container;
    }
}
