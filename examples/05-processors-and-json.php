<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Edrard\Elog\Elog;

$_SERVER['HTTP_X_REQUEST_ID'] = 'example-request-001';

Elog::bootFromJson(
    path: __DIR__ . '/config/processors-json.json',
    projectRoot: elog_example_project_root(),
);

Elog::info('JSON log with processors', ['job' => 'daily-report']);
Elog::error('JSON error with processors', ['retry' => false]);

echo "Processors and JSON example completed. Check examples/logs/json.\n";
