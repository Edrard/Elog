<?php

declare(strict_types=1);

namespace Edrard\Elog\Config;

use Edrard\Elog\Exception\InvalidConfigException;

final readonly class MailConfig
{
    /**
     * @param list<string> $to
     * @param list<string> $importantLevels
     */
    public function __construct(
        public bool $enabled = false,
        public ?string $dsn = null,
        public ?string $from = null,
        public array $to = [],
        public string $subject = 'Elog report',
        public bool $sendOnShutdown = true,
        public bool $separate = false,
        public bool $onlyImportant = true,
        public int $maxBodySize = 1048576,
        public bool $attachLogs = false,
        public array $importantLevels = ['warning', 'error', 'critical', 'alert', 'emergency'],
    ) {
        if ($this->enabled && ($this->dsn === null || $this->dsn === '')) {
            throw InvalidConfigException::missingString('mail.dsn');
        }

        if ($this->enabled && ($this->from === null || $this->from === '')) {
            throw InvalidConfigException::missingString('mail.from');
        }

        if ($this->enabled && $this->to === []) {
            throw InvalidConfigException::invalidList('mail.to');
        }

        if ($this->subject === '') {
            throw InvalidConfigException::missingString('mail.subject');
        }

        if ($this->maxBodySize < 1) {
            throw InvalidConfigException::invalidPositiveInteger('mail.max_body_size');
        }
    }

    /**
     * @param array<string,mixed> $config
     */
    public static function fromArray(array $config): self
    {
        return new self(
            enabled: self::boolValue($config, 'enabled', false),
            dsn: self::nullableStringValue($config, 'dsn'),
            from: self::nullableStringValue($config, 'from'),
            to: self::stringList($config['to'] ?? [], 'mail.to'),
            subject: self::stringValue($config, 'subject', 'Elog report'),
            sendOnShutdown: self::boolValue($config, 'send_on_shutdown', true),
            separate: self::boolValue($config, 'separate', false),
            onlyImportant: self::boolValue($config, 'only_important', true),
            maxBodySize: self::intValue($config, 'max_body_size', 1048576),
            attachLogs: self::boolValue($config, 'attach_logs', false),
            importantLevels: self::stringList(
                $config['important_levels'] ?? ['warning', 'error', 'critical', 'alert', 'emergency'],
                'mail.important_levels',
            ),
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
            throw InvalidConfigException::invalidBoolean('mail.' . $key);
        }

        return $config[$key];
    }

    /**
     * @param array<string,mixed> $config
     */
    private static function nullableStringValue(array $config, string $key): ?string
    {
        if (!array_key_exists($key, $config) || $config[$key] === null) {
            return null;
        }

        if (!is_string($config[$key]) || $config[$key] === '') {
            throw InvalidConfigException::missingString('mail.' . $key);
        }

        return $config[$key];
    }

    /**
     * @param array<string,mixed> $config
     */
    private static function stringValue(array $config, string $key, string $default): string
    {
        if (!array_key_exists($key, $config)) {
            return $default;
        }

        if (!is_string($config[$key]) || $config[$key] === '') {
            throw InvalidConfigException::missingString('mail.' . $key);
        }

        return $config[$key];
    }

    /**
     * @param array<string,mixed> $config
     */
    private static function intValue(array $config, string $key, int $default): int
    {
        if (!array_key_exists($key, $config)) {
            return $default;
        }

        if (!is_int($config[$key])) {
            throw InvalidConfigException::invalidPositiveInteger('mail.' . $key);
        }

        return $config[$key];
    }

    /**
     * @return list<string>
     */
    private static function stringList(mixed $value, string $key): array
    {
        if (!is_array($value) || array_is_list($value) === false) {
            throw InvalidConfigException::invalidList($key);
        }

        $items = [];

        foreach ($value as $item) {
            if (!is_string($item) || $item === '') {
                throw InvalidConfigException::invalidList($key);
            }

            $items[] = $item;
        }

        return $items;
    }
}
