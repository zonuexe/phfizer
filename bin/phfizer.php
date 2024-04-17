<?php

declare(strict_types=1);

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use zonuexe\Phfizer\DependencyInjection\ContainerFactory;

$found = null;
foreach ([__DIR__ . '/../vendor/autoload.php', __DIR__ . '/../../../autoload.php'] as $path) {
    if (file_exists($path)) {
        $found = realpath($path);
        break;
    }
}

if ($found === null) {
    echo 'Classloader not found.', PHP_EOL;
    exit(1);
}

require_once $found;

$container = (new ContainerFactory())->create();
$application = $container->make(Application::class);
assert($application instanceof Application);

if (!stream_isatty(STDIN)) {
    $input = new ArgvInput();
    $input->setStream(STDIN);
}

exit($application->run($input ?? null));
