<?php

declare(strict_types=1);

namespace Edrard\Elog\Config;

use Edrard\Elog\Exception\InvalidConfigException;

final readonly class ElogConfig
{
    /**
     * @param array<string,ChannelConfig> $channels
     */
    public function __construct(
        public string $defaultChannel = 'log',
        public array $channels = ['log' => new ChannelConfig('log')],
        public MailConfig $mail = new MailConfig(),
        public ProcessorConfig $processors = new ProcessorConfig(),
    ) {
        if ($this->defaultChannel === '') {
            throw InvalidConfigException::missingString('default_channel');
        }

        if ($this->channels === []) {
            throw InvalidConfigException::invalidList('channels');
        }

        foreach ($this->channels as $name => $channel) {
            if ($name === '') {
                throw InvalidConfigException::invalidList('channels');
            }

            if ($name !== $channel->name) {
                throw InvalidConfigException::invalidChoice('channels.' . $name . '.name', $channel->name, [$name]);
            }
        }

        if (!array_key_exists($this->defaultChannel, $this->channels)) {
            throw InvalidConfigException::unknownChannel($this->defaultChannel, array_keys($this->channels));
        }
    }

    /**
     * @param array<string,mixed> $config
     */
    public static function fromArray(array $config): self
    {
        $defaultChannel = self::defaultChannel($config['default_channel'] ?? $config['channel'] ?? 'log');

        return new self(
            defaultChannel: $defaultChannel,
            channels: self::channels($config['channels'] ?? null, $defaultChannel),
            mail: MailConfig::fromArray(self::section($config, 'mail')),
            processors: ProcessorConfig::fromArray(self::section($config, 'processors')),
        );
    }

    public function defaultChannelConfig(): ChannelConfig
    {
        return $this->channels[$this->defaultChannel];
    }

    public function withDefaultChannelHandler(HandlerType $handler): self
    {
        return $this->withChannelHandler($this->defaultChannel, $handler);
    }

    public function withChannelHandler(string $channel, HandlerType $handler): self
    {
        if (!array_key_exists($channel, $this->channels)) {
            throw InvalidConfigException::unknownChannel($channel, array_keys($this->channels));
        }

        $channels = $this->channels;
        $channels[$channel] = $channels[$channel]->withHandler($handler);

        return new self(
            defaultChannel: $this->defaultChannel,
            channels: $channels,
            mail: $this->mail,
            processors: $this->processors,
        );
    }

    public function withRunSuffix(string $runSuffix): self
    {
        return $this->mapChannels(
            static fn (ChannelConfig $channel): ChannelConfig => $channel->perRun
                ? $channel->withRunSuffix($runSuffix)
                : $channel,
        );
    }

    /**
     * @param callable(ChannelConfig): ChannelConfig $callback
     */
    public function mapChannels(callable $callback): self
    {
        $channels = [];

        foreach ($this->channels as $name => $channel) {
            $channels[$name] = $callback($channel);
        }

        return new self(
            defaultChannel: $this->defaultChannel,
            channels: $channels,
            mail: $this->mail,
            processors: $this->processors,
        );
    }

    private static function defaultChannel(mixed $channel): string
    {
        if (!is_string($channel) || $channel === '') {
            throw InvalidConfigException::missingString('default_channel');
        }

        return $channel;
    }

    /**
     * @param array<string,mixed> $config
     *
     * @return array<string,mixed>
     */
    private static function section(array $config, string $key): array
    {
        if (!array_key_exists($key, $config)) {
            return [];
        }

        if (!is_array($config[$key])) {
            throw InvalidConfigException::invalidList($key);
        }

        $section = [];

        foreach ($config[$key] as $sectionKey => $value) {
            if (!is_string($sectionKey)) {
                throw InvalidConfigException::invalidList($key);
            }

            $section[$sectionKey] = $value;
        }

        return $section;
    }

    /**
     * @return array<string,ChannelConfig>
     */
    private static function channels(mixed $channels, string $defaultChannel): array
    {
        if ($channels === null) {
            return [
                $defaultChannel => ChannelConfig::fromArray($defaultChannel, []),
            ];
        }

        if (!is_array($channels)) {
            throw InvalidConfigException::invalidList('channels');
        }

        $channelConfigs = [];

        foreach ($channels as $name => $channelConfig) {
            if (!is_string($name) || $name === '') {
                throw InvalidConfigException::invalidList('channels');
            }

            if (!is_array($channelConfig)) {
                throw InvalidConfigException::invalidList('channels.' . $name);
            }

            $channelConfigs[$name] = ChannelConfig::fromArray($name, self::stringKeyArray($channelConfig, 'channels.' . $name));
        }

        return $channelConfigs;
    }

    /**
     * @param array<mixed,mixed> $array
     *
     * @return array<string,mixed>
     */
    private static function stringKeyArray(array $array, string $key): array
    {
        $result = [];

        foreach ($array as $itemKey => $value) {
            if (!is_string($itemKey)) {
                throw InvalidConfigException::invalidList($key);
            }

            $result[$itemKey] = $value;
        }

        return $result;
    }
}
