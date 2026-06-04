<?php

declare(strict_types=1);

namespace Edrard\Elog\Tests;

use Edrard\Elog\Config\ChannelConfig;
use Edrard\Elog\Config\ElogConfig;
use Edrard\Elog\Config\HandlerType;
use Edrard\Elog\Exception\InvalidConfigException;
use Edrard\Elog\LogManager;
use PHPUnit\Framework\TestCase;

final class LogManagerTest extends TestCase
{
    public function testItReturnsDefaultChannelAndCachesLoggerInstance(): void
    {
        $manager = new LogManager(new ElogConfig(
            defaultChannel: 'app',
            channels: [
                'app' => new ChannelConfig('app', handler: HandlerType::Null),
            ],
        ));

        $logger = $manager->channel();

        self::assertSame($logger, $manager->channel('app'));
    }

    public function testItReconfiguresChannels(): void
    {
        $manager = new LogManager(new ElogConfig(
            defaultChannel: 'app',
            channels: [
                'app' => new ChannelConfig('app', handler: HandlerType::Null),
            ],
        ));

        $before = $manager->channel();

        $manager->reconfigure(new ElogConfig(
            defaultChannel: 'cli',
            channels: [
                'cli' => new ChannelConfig('cli', handler: HandlerType::Stdout),
            ],
        ));

        $after = $manager->channel();

        self::assertNotSame($before, $after);
        self::assertSame('cli', $manager->config()->defaultChannel);
    }

    public function testItRejectsUnknownChannels(): void
    {
        $manager = new LogManager(new ElogConfig(
            defaultChannel: 'app',
            channels: [
                'app' => new ChannelConfig('app', handler: HandlerType::Null),
            ],
        ));

        $this->expectException(InvalidConfigException::class);

        $manager->channel('missing');
    }
}
