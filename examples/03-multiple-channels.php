<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Edrard\Elog\Elog;

Elog::bootFromJson(
    path: __DIR__ . '/config/multiple-channels.json',
    projectRoot: elog_example_project_root(),
);

Elog::info('Default app channel message');
Elog::info('Import started', ['source' => 'feed.csv'], 'import');
Elog::error('Shared failure visible in both channels', [], ['app', 'import']);
Elog::warning('This warning is ignored by the import channel because import allows info and error only.', [], 'import');

echo "Multiple channels example completed. Check examples/logs/app and examples/logs/import.\n";
