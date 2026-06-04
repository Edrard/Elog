<?php

declare(strict_types=1);

namespace Edrard\Elog\Config;

use Edrard\Elog\Exception\InvalidConfigException;

final readonly class ProcessorConfig
{
    public function __construct(
        public bool $enabled = false,
        public bool $memoryUsage = false,
        public bool $processId = false,
        public bool $uid = false,
        public bool $requestId = false,
    ) {
    }

    /**
     * @param array<string,mixed> $config
     */
    public static function fromArray(array $config): self
    {
        return new self(
            enabled: self::boolValue($config, 'enabled', false),
            memoryUsage: self::boolValue($config, 'memory_usage', false),
            processId: self::boolValue($config, 'process_id', false),
            uid: self::boolValue($config, 'uid', false),
            requestId: self::boolValue($config, 'request_id', false),
        );
    }

    /**
     * @param array<string,mixed> $config
     */
    private static function boolValue(array $config, string $key, bool $default): bool
    {
        if (!array_key_exists($key, $config)) {
            return $default;
        }

        if (!is_bool($config[$key])) {
            throw InvalidConfigException::invalidBoolean('processors.' . $key);
        }

        return $config[$key];
    }
}
