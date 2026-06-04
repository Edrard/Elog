<?php

declare(strict_types=1);

namespace Edrard\Elog\Mail;

use Edrard\Elog\Config\MailConfig;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

final readonly class LogMailer
{
    public function __construct(
        private MailerInterface $mailer,
    ) {
    }

    public static function fromConfig(MailConfig $config): self
    {
        return new self(new Mailer(Transport::fromDsn((string) $config->dsn)));
    }

    public function send(MailConfig $config, LogDigest $digest): void
    {
        $email = (new Email())
            ->from((string) $config->from)
            ->to(...$config->to)
            ->subject($digest->subject)
            ->text($digest->body);

        foreach ($digest->attachments as $attachment) {
            $email->attachFromPath($attachment);
        }

        $this->mailer->send($email);
    }
}
