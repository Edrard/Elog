<?php

declare(strict_types=1);

namespace Edrard\Elog\Tests;

use Edrard\Elog\Config\ChannelConfig;
use Edrard\Elog\Config\ElogConfig;
use Edrard\Elog\Config\HandlerType;
use Edrard\Elog\Elog;
use Edrard\Elog\Exception\ElogException;
use PHPUnit\Framework\TestCase;

final class ElogTest extends TestCase
{
    private string $runtimePath;

    protected function setUp(): void
    {
        $this->runtimePath = sys_get_temp_dir() . '/elog-facade-tests/' . bin2hex(random_bytes(8));
    }

    protected function tearDown(): void
    {
        Elog::reset();
        $this->removeDirectory($this->runtimePath);
    }

    public function testItRequiresBootBeforeLogging(): void
    {
        $this->expectException(ElogException::class);

        Elog::info('Not booted yet');
    }

    public function testItBootsAndWritesToDefaultChannel(): void
    {
        Elog::boot(new ElogConfig(
            defaultChannel: 'app',
            channels: [
                'app' => new ChannelConfig('app', handler: HandlerType::File, path: $this->runtimePath),
            ],
        ));

        Elog::info('Facade info');
        Elog::reset();

        $files = glob($this->runtimePath . '/info-*.log');

        self::assertIsArray($files);
        self::assertCount(1, $files);
        self::assertStringContainsString('Facade info', (string) file_get_contents($files[0]));
    }

    public function testItWritesToMultipleChannels(): void
    {
        Elog::boot(new ElogConfig(
            defaultChannel: 'app',
            channels: [
                'app' => new ChannelConfig('app', handler: HandlerType::File, path: $this->runtimePath),
                'import' => new ChannelConfig('import', handler: HandlerType::File, path: $this->runtimePath),
            ],
        ));

        Elog::info('Shared message', channel: ['app', 'import']);
        Elog::reset();

        $files = glob($this->runtimePath . '/info-*.log');

        self::assertIsArray($files);
        self::assertCount(1, $files);

        $contents = (string) file_get_contents($files[0]);

        self::assertStringContainsString('app.INFO: Shared message', $contents);
        self::assertStringContainsString('import.INFO: Shared message', $contents);
    }

    public function testItCanReconfigureThroughFacade(): void
    {
        Elog::boot(new ElogConfig(
            defaultChannel: 'app',
            channels: [
                'app' => new ChannelConfig('app', handler: HandlerType::Null),
            ],
        ));

        Elog::reconfigure(new ElogConfig(
            defaultChannel: 'cli',
            channels: [
                'cli' => new ChannelConfig('cli', handler: HandlerType::Null),
            ],
        ));

        self::assertSame('cli', Elog::manager()->config()->defaultChannel);
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $child = $path . DIRECTORY_SEPARATOR . $item;

            if (is_dir($child)) {
                $this->removeDirectory($child);

                continue;
            }

            unlink($child);
        }

        rmdir($path);
    }
}
