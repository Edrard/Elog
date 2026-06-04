<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Edrard\Elog\Elog;

Elog::bootFromJson(
    path: __DIR__ . '/config/mail-digest.json',
    projectRoot: elog_example_project_root(),
);

Elog::info('This info message is not included because mail.only_important is enabled.');
Elog::error('This error message is included in the mail digest.');
Elog::warning('This warning message is included in the mail digest.');
Elog::critical('This critical message is included in the mail digest.');

$sent = Elog::sendMailDigest();

echo 'Mail digest example completed. Sent messages: ' . $sent . "\n";
