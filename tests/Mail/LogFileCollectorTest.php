<?php

declare(strict_types=1);

namespace Edrard\Elog\Tests\Mail;

use DateTimeImmutable;
use Edrard\Elog\Config\ChannelConfig;
use Edrard\Elog\Mail\LogFileCollector;
use Monolog\Level;
use PHPUnit\Framework\TestCase;

final class LogFileCollectorTest extends TestCase
{
    private string $runtimePath;

    protected function setUp(): void
    {
        $this->runtimePath = sys_get_temp_dir() . '/elog-mail-collector-tests/' . bin2hex(random_bytes(8));
        mkdir($this->runtimePath, recursive: true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->runtimePath);
    }

    public function testItCollectsExistingRotatedLogFilesForConfiguredLevels(): void
    {
        file_put_contents($this->runtimePath . '/info-2026-06-04.log', 'info');
        file_put_contents($this->runtimePath . '/error-2026-06-04.log', 'error');

        $files = (new LogFileCollector())->collect(
            new ChannelConfig(
                name: 'app',
                path: $this->runtimePath,
                levels: [Level::Info, Level::Warning, Level::Error],
            ),
            new DateTimeImmutable('2026-06-04'),
        );

        self::assertCount(2, $files);
        self::assertSame('info', $files[0]->label);
        self::assertSame(['info'], $files[0]->levels);
        self::assertSame('warning error', $files[1]->label);
        self::assertSame(['warning', 'error'], $files[1]->levels);
    }

    public function testItCollectsPerRunLogFilesForConfiguredLevels(): void
    {
        file_put_contents($this->runtimePath . '/error-2026-06-04_14-21-39_ab12cd.log', 'error');
        file_put_contents($this->runtimePath . '/error-2026-06-04_14-21-40_cd34ef.log', 'other run');

        $files = (new LogFileCollector())->collect(new ChannelConfig(
            name: 'app',
            path: $this->runtimePath,
            levels: [Level::Warning, Level::Error],
            perRun: true,
            runSuffix: '2026-06-04_14-21-39_ab12cd',
        ));

        self::assertCount(1, $files);
        self::assertSame('warning error', $files[0]->label);
        self::assertSame(['warning', 'error'], $files[0]->levels);
        self::assertStringEndsWith('error-2026-06-04_14-21-39_ab12cd.log', $files[0]->path);
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
