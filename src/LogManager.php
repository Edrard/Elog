<?php

declare(strict_types=1);

namespace Edrard\Elog;

use Edrard\Elog\Config\ElogConfig;
use Edrard\Elog\Exception\InvalidConfigException;
use Psr\Log\LoggerInterface;

final class LogManager
{
    /**
     * @var array<string,LoggerInterface>
     */
    private array $channels = [];

    public function __construct(
        private ElogConfig $config,
        private readonly LoggerFactory $factory = new LoggerFactory(),
    ) {
    }

    public function channel(?string $name = null): LoggerInterface
    {
        $name ??= $this->config->defaultChannel;

        if (!array_key_exists($name, $this->config->channels)) {
            throw InvalidConfigException::unknownChannel($name, array_keys($this->config->channels));
        }

        return $this->channels[$name] ??= $this->factory->create(
            $this->config->channels[$name],
            $this->config->processors,
        );
    }

    public function reconfigure(ElogConfig $config): void
    {
        $this->close();

        $this->config = $config;
        $this->channels = [];
    }

    public function close(): void
    {
        foreach ($this->channels as $logger) {
            if (method_exists($logger, 'close')) {
                $logger->close();
            }
        }
    }

    public function config(): ElogConfig
    {
        return $this->config;
    }
}
