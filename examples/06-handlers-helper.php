<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Edrard\Elog\Handler\Handlers;
use Monolog\Logger;

$logger = new Logger('manual', Handlers::stdout());

$logger->info('This example uses Elog handler helpers with a plain Monolog logger.');
$logger->critical('Ready-made handlers are useful for manual or integration scenarios.');
