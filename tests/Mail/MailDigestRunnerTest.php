<?php

declare(strict_types=1);

namespace Edrard\Elog\Tests\Mail;

use DateTimeInterface;
use Edrard\Elog\Config\ChannelConfig;
use Edrard\Elog\Config\ElogConfig;
use Edrard\Elog\Config\HandlerType;
use Edrard\Elog\Config\MailConfig;
use Edrard\Elog\Mail\LogFile;
use Edrard\Elog\Mail\LogFileCollector;
use Edrard\Elog\Mail\LogMailer;
use Edrard\Elog\Mail\MailDigestRunner;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

final class MailDigestRunnerTest extends TestCase
{
    public function testItDoesNothingWhenMailIsDisabled(): void
    {
        $mailer = new RecordingMailer();
        $runner = new MailDigestRunner(
            collector: new StubCollector([
                new LogFile(__FILE__, 'error', ['error']),
            ]),
            mailer: new LogMailer($mailer),
        );

        $sent = $runner->send(new ElogConfig(
            defaultChannel: 'app',
            channels: [
                'app' => new ChannelConfig('app'),
            ],
            mail: new MailConfig(enabled: false),
        ));

        self::assertSame(0, $sent);
        self::assertSame([], $mailer->messages);
    }

    public function testItSkipsStdoutChannels(): void
    {
        $mailer = new RecordingMailer();
        $runner = new MailDigestRunner(
            collector: new StubCollector([
                new LogFile(__FILE__, 'error', ['error']),
            ]),
            mailer: new LogMailer($mailer),
        );

        $sent = $runner->send(new ElogConfig(
            defaultChannel: 'app',
            channels: [
                'app' => new ChannelConfig('app', handler: HandlerType::Stdout),
            ],
            mail: new MailConfig(
                enabled: true,
                dsn: 'null://null',
                from: 'from@example.com',
                to: ['to@example.com'],
            ),
        ));

        self::assertSame(0, $sent);
        self::assertSame([], $mailer->messages);
    }

    public function testItSendsDigestForFileChannel(): void
    {
        $mailer = new RecordingMailer();
        $runner = new MailDigestRunner(
            collector: new StubCollector([
                new LogFile(__FILE__, 'error', ['error']),
            ]),
            mailer: new LogMailer($mailer),
        );

        $sent = $runner->send(new ElogConfig(
            defaultChannel: 'app',
            channels: [
                'app' => new ChannelConfig('app'),
            ],
            mail: new MailConfig(
                enabled: true,
                dsn: 'null://null',
                from: 'from@example.com',
                to: ['to@example.com'],
            ),
        ));

        self::assertSame(1, $sent);
        self::assertCount(1, $mailer->messages);
        self::assertSame('[app Elog report]', $mailer->messages[0]->getSubject());
    }
}

final class StubCollector extends LogFileCollector
{
    /**
     * @param list<LogFile> $files
     */
    public function __construct(
        private readonly array $files,
    ) {
    }

    public function collect(ChannelConfig $channel, ?DateTimeInterface $date = null): array
    {
        return $this->files;
    }
}

final class RecordingMailer implements MailerInterface
{
    /**
     * @var list<Email>
     */
    public array $messages = [];

    public function send(RawMessage $message, ?Envelope $envelope = null): void
    {
        if ($message instanceof Email) {
            $this->messages[] = $message;
        }
    }
}
