<?php

declare(strict_types=1);

namespace Edrard\Elog\Tests\Config;

use Edrard\Elog\Config\HandlerType;
use Edrard\Elog\Config\JsonConfigLoader;
use Edrard\Elog\Exception\InvalidConfigException;
use PHPUnit\Framework\TestCase;

final class JsonConfigLoaderTest extends TestCase
{
    public function testItLoadsJsonConfigAndResolvesFilePathsFromProjectRoot(): void
    {
        $config = JsonConfigLoader::load(
            __DIR__ . '/fixtures/elog.valid.json',
            'D:/Work/Project',
        );

        self::assertSame('app', $config->defaultChannel);
        self::assertSame(HandlerType::File, $config->defaultChannelConfig()->handler);
        self::assertSame(
            implode(DIRECTORY_SEPARATOR, ['D:', 'Work', 'Project', 'logs']),
            $config->defaultChannelConfig()->path,
        );
    }

    public function testItDoesNotResolvePathForStdoutChannel(): void
    {
        $config = JsonConfigLoader::load(
            __DIR__ . '/fixtures/elog.stdout.json',
            'D:/Work/Project',
        );

        self::assertSame(HandlerType::Stdout, $config->defaultChannelConfig()->handler);
        self::assertSame('logs', $config->defaultChannelConfig()->path);
    }

    public function testItRejectsInvalidJson(): void
    {
        $this->expectException(InvalidConfigException::class);

        JsonConfigLoader::load(__DIR__ . '/fixtures/elog.invalid.json', 'D:/Work/Project');
    }

    public function testItRejectsMissingFile(): void
    {
        $this->expectException(InvalidConfigException::class);

        JsonConfigLoader::load(__DIR__ . '/fixtures/missing.json', 'D:/Work/Project');
    }
}
