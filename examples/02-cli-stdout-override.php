<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Edrard\Elog\Config\HandlerType;
use Edrard\Elog\Config\JsonConfigLoader;
use Edrard\Elog\Elog;

$config = JsonConfigLoader::load(
    path: __DIR__ . '/config/basic-file.json',
    projectRoot: elog_example_project_root(),
);

if (in_array('--stdout', $argv, true)) {
    $config = $config->withDefaultChannelHandler(HandlerType::Stdout);
}

Elog::boot($config);

Elog::info('CLI process started', ['stdout' => in_array('--stdout', $argv, true)]);
Elog::critical('CLI process finished with a critical sample message');

echo "CLI override example completed.\n";
