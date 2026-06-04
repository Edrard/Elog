<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Edrard\Elog\Elog;

Elog::bootFromJson(
    path: __DIR__ . '/config/basic-file.json',
    projectRoot: elog_example_project_root(),
);

Elog::debug('This debug message is ignored by the configured level allow-list.');
Elog::info('Application started', ['example' => 'basic-json']);
Elog::warning('Something should be checked', ['code' => 1001]);
Elog::error('Something failed', ['code' => 500]);

echo "Basic JSON example completed. Check examples/logs/basic.\n";
