<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Edrard\Elog\Config\ChannelConfig;
use Edrard\Elog\Config\ElogConfig;
use Edrard\Elog\Config\HandlerType;
use Edrard\Elog\Config\ProcessorConfig;
use Edrard\Elog\Elog;
use Monolog\Level;

$config = new ElogConfig(
    defaultChannel: 'cli',
    channels: [
        'cli' => new ChannelConfig(
            name: 'cli',
            handler: HandlerType::Stdout,
            levels: [Level::Debug, Level::Info, Level::Error],
        ),
    ],
    processors: new ProcessorConfig(enabled: false),
);

Elog::boot($config);

Elog::debug('Typed config debug message');
Elog::info('Typed config info message');
Elog::warning('This warning is ignored because the cli channel allow-list excludes it.');
Elog::error('Typed config error message');
