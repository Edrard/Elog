<?php

declare(strict_types=1);

namespace Edrard\Elog\Tests\Mail;

use Edrard\Elog\Config\MailConfig;
use Edrard\Elog\Mail\LogDigestBuilder;
use Edrard\Elog\Mail\LogFile;
use PHPUnit\Framework\TestCase;

final class LogDigestBuilderTest extends TestCase
{
    private string $runtimePath;

    protected function setUp(): void
    {
        $this->runtimePath = sys_get_temp_dir() . '/elog-mail-builder-tests/' . bin2hex(random_bytes(8));
        mkdir($this->runtimePath, recursive: true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->runtimePath);
    }

    public function testItBuildsCombinedImportantDigestByDefault(): void
    {
        $info = $this->logFile('info.log', 'info body', 'info', ['info']);
        $warning = $this->logFile('warning.log', 'warning body', 'warning', ['warning']);
        $error = $this->logFile('error.log', 'error body', 'error', ['error']);

        $digests = (new LogDigestBuilder())->build(
            new MailConfig(enabled: true, dsn: 'null://null', from: 'from@example.com', to: ['to@example.com']),
            'app',
            [$info, $warning, $error],
        );

        self::assertCount(1, $digests);
        self::assertSame('[app Elog report]', $digests[0]->subject);
        self::assertStringContainsString('warning body', $digests[0]->body);
        self::assertStringContainsString('error body', $digests[0]->body);
        self::assertStringNotContainsString('info body', $digests[0]->body);
    }

    public function testItBuildsSeparateDigestsWhenSeparateIsEnabled(): void
    {
        $warning = $this->logFile('warning.log', 'warning body', 'warning', ['warning']);
        $error = $this->logFile('error.log', 'error body', 'error', ['error']);

        $digests = (new LogDigestBuilder())->build(
            new MailConfig(
                enabled: true,
                dsn: 'null://null',
                from: 'from@example.com',
                to: ['to@example.com'],
                separate: true,
            ),
            'app',
            [$warning, $error],
        );

        self::assertCount(2, $digests);
        self::assertSame('[app warning Elog report]', $digests[0]->subject);
        self::assertSame('[app error Elog report]', $digests[1]->subject);
    }

    public function testItBuildsCombinedDigestWhenImportantOnlyAndSeparateAreDisabled(): void
    {
        $info = $this->logFile('info.log', 'info body', 'info', ['info']);
        $error = $this->logFile('error.log', 'error body', 'error', ['error']);

        $digests = (new LogDigestBuilder())->build(
            new MailConfig(
                enabled: true,
                dsn: 'null://null',
                from: 'from@example.com',
                to: ['to@example.com'],
                separate: false,
                onlyImportant: false,
            ),
            'app',
            [$info, $error],
        );

        self::assertCount(1, $digests);
        self::assertSame('[app Elog report]', $digests[0]->subject);
        self::assertStringContainsString('info body', $digests[0]->body);
        self::assertStringContainsString('error body', $digests[0]->body);
    }

    public function testItUsesAttachmentsWhenConfigured(): void
    {
        $error = $this->logFile('error.log', 'error body', 'error', ['error']);

        $digests = (new LogDigestBuilder())->build(
            new MailConfig(
                enabled: true,
                dsn: 'null://null',
                from: 'from@example.com',
                to: ['to@example.com'],
                attachLogs: true,
            ),
            'app',
            [$error],
        );

        self::assertSame([$error->path], $digests[0]->attachments);
        self::assertStringContainsString('Log files are attached', $digests[0]->body);
        self::assertStringNotContainsString('error body', $digests[0]->body);
    }

    /**
     * @param list<string> $levels
     */
    private function logFile(string $name, string $content, string $label, array $levels): LogFile
    {
        $path = $this->runtimePath . DIRECTORY_SEPARATOR . $name;
        file_put_contents($path, $content);

        return new LogFile($path, $label, $levels);
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

            unlink($path . DIRECTORY_SEPARATOR . $item);
        }

        rmdir($path);
    }
}
