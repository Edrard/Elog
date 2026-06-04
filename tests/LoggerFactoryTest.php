<?php

declare(strict_types=1);

namespace Edrard\Elog\Tests;

use Edrard\Elog\Config\ChannelConfig;
use Edrard\Elog\Config\HandlerType;
use Edrard\Elog\Config\ProcessorConfig;
use Edrard\Elog\LoggerFactory;
use Monolog\Level;
use PHPUnit\Framework\TestCase;

final class LoggerFactoryTest extends TestCase
{
    private string $runtimePath;

    protected function setUp(): void
    {
        $this->runtimePath = sys_get_temp_dir() . '/elog-tests/' . bin2hex(random_bytes(8));
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->runtimePath);
    }

    public function testItCreatesFileLoggerAndRoutesRecordsByConfiguredLevelFiles(): void
    {
        $logger = (new LoggerFactory())->create(new ChannelConfig(
            name: 'app',
            handler: HandlerType::File,
            path: $this->runtimePath,
            levels: [Level::Info, Level::Error],
            fileNames: [
                'info' => 'info.log',
                'error' => 'error.log',
            ],
        ));

        $logger->debug('Hidden debug');
        $logger->info('Visible info');
        $logger->error('Visible error');
        $logger->close();

        $infoFiles = glob($this->runtimePath . '/info-*.log');
        $errorFiles = glob($this->runtimePath . '/error-*.log');

        self::assertIsArray($infoFiles);
        self::assertIsArray($errorFiles);
        self::assertCount(1, $infoFiles);
        self::assertCount(1, $errorFiles);
        self::assertStringContainsString('Visible info', (string) file_get_contents($infoFiles[0]));
        self::assertStringNotContainsString('Hidden debug', (string) file_get_contents($infoFiles[0]));
        self::assertStringContainsString('Visible error', (string) file_get_contents($errorFiles[0]));
    }

    public function testItCreatesStdoutLoggerWithExactConfiguredLevels(): void
    {
        $logger = (new LoggerFactory())->create(new ChannelConfig(
            name: 'cli',
            handler: HandlerType::Stdout,
            levels: [Level::Info, Level::Error],
        ));

        self::assertFalse($logger->isHandling(Level::Debug));
        self::assertTrue($logger->isHandling(Level::Info));
        self::assertFalse($logger->isHandling(Level::Warning));
        self::assertTrue($logger->isHandling(Level::Error));
    }

    public function testItCreatesPerRunFileLoggerWithRunSuffix(): void
    {
        $logger = (new LoggerFactory())->create(new ChannelConfig(
            name: 'app',
            handler: HandlerType::File,
            path: $this->runtimePath,
            levels: [Level::Error],
            perRun: true,
            fileNames: [
                'error' => 'error.log',
            ],
            runSuffix: '2026-06-04_14-21-39_ab12cd',
        ));

        $logger->error('Visible error');
        $logger->close();

        $path = $this->runtimePath . '/error-2026-06-04_14-21-39_ab12cd.log';

        self::assertFileExists($path);
        self::assertStringContainsString('Visible error', (string) file_get_contents($path));
    }

    public function testItCleansOldFilesPerPhysicalFileGroup(): void
    {
        mkdir($this->runtimePath, recursive: true);
        file_put_contents($this->runtimePath . '/info-1900-01-01.log', 'oldest');
        file_put_contents($this->runtimePath . '/info-1900-01-02.log', 'old');
        file_put_contents($this->runtimePath . '/error-1900-01-01.log', 'old error');

        $logger = (new LoggerFactory())->create(new ChannelConfig(
            name: 'app',
            handler: HandlerType::File,
            path: $this->runtimePath,
            maxFiles: 2,
            levels: [Level::Info, Level::Error],
            fileNames: [
                'info' => 'info.log',
                'error' => 'error.log',
            ],
        ));

        $logger->info('Visible info');
        $logger->close();

        $infoFiles = glob($this->runtimePath . '/info-*.log');
        $errorFiles = glob($this->runtimePath . '/error-*.log');

        self::assertIsArray($infoFiles);
        self::assertIsArray($errorFiles);
        self::assertCount(2, $infoFiles);
        self::assertCount(1, $errorFiles);
        self::assertFileDoesNotExist($this->runtimePath . '/info-1900-01-01.log');
        self::assertFileExists($this->runtimePath . '/error-1900-01-01.log');
    }

    public function testItKeepsAllFilesWhenMaxFilesIsDisabled(): void
    {
        mkdir($this->runtimePath, recursive: true);
        file_put_contents($this->runtimePath . '/info-1900-01-01.log', 'oldest');
        file_put_contents($this->runtimePath . '/info-1900-01-02.log', 'old');

        $logger = (new LoggerFactory())->create(new ChannelConfig(
            name: 'app',
            handler: HandlerType::File,
            path: $this->runtimePath,
            maxFiles: false,
            levels: [Level::Info],
            fileNames: [
                'info' => 'info.log',
            ],
        ));

        $logger->info('Visible info');
        $logger->close();

        $infoFiles = glob($this->runtimePath . '/info-*.log');

        self::assertIsArray($infoFiles);
        self::assertGreaterThanOrEqual(3, count($infoFiles));
        self::assertFileExists($this->runtimePath . '/info-1900-01-01.log');
        self::assertFileExists($this->runtimePath . '/info-1900-01-02.log');
    }

    public function testItAddsProcessorsOnlyWhenTheyAreEnabled(): void
    {
        $factory = new LoggerFactory([
            'HTTP_X_REQUEST_ID' => 'request-42',
        ]);

        $disabled = $factory->create(new ChannelConfig('app', handler: HandlerType::Null));

        $enabled = $factory->create(
            new ChannelConfig('app', handler: HandlerType::Null),
            new ProcessorConfig(
                enabled: true,
                memoryUsage: true,
                processId: true,
                uid: true,
                requestId: true,
            ),
        );

        self::assertCount(0, $disabled->getProcessors());
        self::assertCount(4, $enabled->getProcessors());
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
