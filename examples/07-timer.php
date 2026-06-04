<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Edrard\Elog\Timer;

Timer::start();
Timer::start('import');

usleep(10000);

echo 'Global elapsed: ' . Timer::elapsed(precision: 4) . " sec\n";
echo 'Import elapsed while still running: ' . Timer::elapsed('import', 4) . " sec\n";

usleep(10000);

echo 'Import stopped at: ' . Timer::stop('import', 4) . " sec\n";
echo 'Import elapsed after stop remains stable: ' . Timer::elapsed('import', 4) . " sec\n";

Timer::reset('import');
Timer::reset();
