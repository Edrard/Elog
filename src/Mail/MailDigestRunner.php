<?php

declare(strict_types=1);

namespace Edrard\Elog\Mail;

use Edrard\Elog\Config\ElogConfig;
use Edrard\Elog\Config\HandlerType;
use Edrard\Elog\Exception\InvalidConfigException;

final readonly class MailDigestRunner
{
    public function __construct(
        private LogFileCollector $collector = new LogFileCollector(),
        private LogDigestBuilder $builder = new LogDigestBuilder(),
        private ?LogMailer $mailer = null,
    ) {
    }

    /**
     * @param string|list<string>|null $channel
     */
    public function send(ElogConfig $config, string|array|null $channel = null): int
    {
        if (!$config->mail->enabled) {
            return 0;
        }

        $mailer = $this->mailer ?? LogMailer::fromConfig($config->mail);
        $sent = 0;

        foreach ($this->channels($config, $channel) as $channelName) {
            $channelConfig = $config->channels[$channelName];

            if ($channelConfig->handler !== HandlerType::File) {
                continue;
            }

            foreach ($this->builder->build(
                $config->mail,
                $channelName,
                $this->collector->collect($channelConfig),
            ) as $digest) {
                $mailer->send($config->mail, $digest);
                ++$sent;
            }
        }

        return $sent;
    }

    /**
     * @param string|list<string>|null $channel
     *
     * @return list<string>
     */
    private function channels(ElogConfig $config, string|array|null $channel): array
    {
        if ($channel === null) {
            return [$config->defaultChannel];
        }

        if (is_string($channel)) {
            $channel = [$channel];
        }

        foreach ($channel as $channelName) {
            if (!array_key_exists($channelName, $config->channels)) {
                throw InvalidConfigException::unknownChannel($channelName, array_keys($config->channels));
            }
        }

        return $channel;
    }
}
