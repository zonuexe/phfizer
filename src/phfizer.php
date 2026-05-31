<?php

declare(strict_types=1);

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use zonuexe\Phfizer\DependencyInjection\ContainerFactory;
use function filter_var;

$candidates = [];

// Composer (>=2.2) sets this global in the generated bin proxy and points to the
// autoloader of the project that requires this package. Prefer it so that the
// classloader is resolved correctly regardless of the vendor-dir name or whether
// the package is installed via a symlinked `path` repository.
$autoload_path = filter_var($GLOBALS['_composer_autoload_path'] ?? false);
if ($autoload_path !== false) {
    $candidates[] = $autoload_path;
}

// Fallbacks for direct execution (running from this repository checkout, or older
// Composer versions that do not provide the global above).
$candidates[] = __DIR__ . '/../vendor/autoload.php';
$candidates[] = __DIR__ . '/../../../autoload.php';

$found = null;
foreach ($candidates as $path) {
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

$input = null;
if (!stream_isatty(STDIN)) {
    $input = new ArgvInput();
    $input->setStream(STDIN);
}

exit($application->run($input));
