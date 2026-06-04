<?php

declare(strict_types=1);

namespace Edrard\Elog\Tests\Config;

use Edrard\Elog\Config\ChannelConfig;
use Edrard\Elog\Config\ElogConfig;
use Edrard\Elog\Config\HandlerType;
use Edrard\Elog\Exception\InvalidConfigException;
use Monolog\Level;
use PHPUnit\Framework\TestCase;

final class ElogConfigTest extends TestCase
{
    public function testItUsesSafeDefaults(): void
    {
        $config = ElogConfig::fromArray([]);

        self::assertSame('log', $config->defaultChannel);
        self::assertArrayHasKey('log', $config->channels);
        self::assertSame(HandlerType::File, $config->defaultChannelConfig()->handler);
        self::assertSame('logs', $config->defaultChannelConfig()->path);
        self::assertSame(14, $config->defaultChannelConfig()->maxFiles);
        self::assertSame(ChannelConfig::FORMAT_LINE, $config->defaultChannelConfig()->format);
        self::assertFalse($config->mail->enabled);
        self::assertFalse($config->processors->enabled);
    }

    public function testItBuildsConfigFromArray(): void
    {
        $config = ElogConfig::fromArray([
            'default_channel' => 'app',
            'channels' => [
                'app' => [
                    'handler' => 'file',
                    'path' => 'var/log',
                    'max_files' => 14,
                    'levels' => ['debug', 'error'],
                    'format' => 'json',
                    'per_run' => true,
                ],
            ],
            'mail' => [
                'enabled' => true,
                'dsn' => 'smtp://user:pass@smtp.example.com:587',
                'from' => 'server@example.com',
                'to' => ['admin@example.com'],
                'subject' => 'Server report',
            ],
            'processors' => [
                'enabled' => true,
                'memory_usage' => true,
                'process_id' => true,
            ],
        ]);

        $channel = $config->defaultChannelConfig();

        self::assertSame('app', $config->defaultChannel);
        self::assertSame('app', $channel->name);
        self::assertSame(HandlerType::File, $channel->handler);
        self::assertSame('var/log', $channel->path);
        self::assertSame([Level::Debug, Level::Error], $channel->levels);
        self::assertSame(ChannelConfig::FORMAT_JSON, $channel->format);
        self::assertTrue($channel->perRun);
        self::assertTrue($config->mail->enabled);
        self::assertSame(['admin@example.com'], $config->mail->to);
        self::assertTrue($config->processors->enabled);
        self::assertTrue($config->processors->memoryUsage);
        self::assertTrue($config->processors->processId);
        self::assertFalse($config->processors->uid);
    }

    public function testItAllowsDisablingMaxFilesCleanup(): void
    {
        $config = ElogConfig::fromArray([
            'channels' => [
                'log' => [
                    'max_files' => false,
                ],
            ],
        ]);

        self::assertFalse($config->defaultChannelConfig()->maxFiles);
    }

    public function testItRejectsInvalidMaxFiles(): void
    {
        $this->expectException(InvalidConfigException::class);

        ElogConfig::fromArray([
            'channels' => [
                'log' => [
                    'max_files' => 0,
                ],
            ],
        ]);
    }

    public function testItRequiresMailSettingsOnlyWhenMailIsEnabled(): void
    {
        $config = ElogConfig::fromArray([
            'mail' => [
                'enabled' => false,
            ],
        ]);

        self::assertFalse($config->mail->enabled);

        $this->expectException(InvalidConfigException::class);

        ElogConfig::fromArray([
            'mail' => [
                'enabled' => true,
            ],
        ]);
    }

    public function testItRejectsInvalidFileFormat(): void
    {
        $this->expectException(InvalidConfigException::class);

        ElogConfig::fromArray([
            'channels' => [
                'app' => [
                    'format' => 'xml',
                ],
            ],
        ]);
    }

    public function testItOverridesDefaultChannelHandlerWithoutMutatingOriginalConfig(): void
    {
        $config = ElogConfig::fromArray([
            'default_channel' => 'app',
            'channels' => [
                'app' => [
                    'handler' => 'file',
                ],
            ],
        ]);

        $overridden = $config->withDefaultChannelHandler(HandlerType::Stdout);

        self::assertSame(HandlerType::File, $config->defaultChannelConfig()->handler);
        self::assertSame(HandlerType::Stdout, $overridden->defaultChannelConfig()->handler);
    }

    public function testItRejectsUnknownDefaultChannel(): void
    {
        $this->expectException(InvalidConfigException::class);

        ElogConfig::fromArray([
            'default_channel' => 'missing',
            'channels' => [
                'app' => [
                    'handler' => 'file',
                ],
            ],
        ]);
    }
}
