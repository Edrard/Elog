<?php

declare(strict_types=1);

namespace Edrard\Elog\Config;

use Edrard\Elog\Exception\InvalidConfigException;
use Monolog\Level;

final readonly class ChannelConfig
{
    public const FORMAT_LINE = 'line';
    public const FORMAT_JSON = 'json';

    /**
     * @param int|false            $maxFiles
     * @param list<Level>          $levels
     * @param array<string,string> $fileNames
     */
    public function __construct(
        public string $name,
        public HandlerType $handler = HandlerType::File,
        public string $path = 'logs',
        public int|false $maxFiles = 14,
        public array $levels = [Level::Info, Level::Warning, Level::Error, Level::Critical],
        public string $format = self::FORMAT_LINE,
        public bool $perRun = false,
        public array $fileNames = [
            'debug' => 'debug.log',
            'info' => 'info.log',
            'notice' => 'info.log',
            'warning' => 'error.log',
            'error' => 'error.log',
            'critical' => 'error.log',
            'alert' => 'error.log',
            'emergency' => 'error.log',
        ],
        public ?string $runSuffix = null,
    ) {
        if ($this->name === '') {
            throw InvalidConfigException::missingString('channel.name');
        }

        if ($this->handler === HandlerType::File && $this->path === '') {
            throw InvalidConfigException::missingString('channels.' . $this->name . '.path');
        }

        if ($this->maxFiles !== false && $this->maxFiles < 1) {
            throw InvalidConfigException::invalidPositiveIntegerOrFalse('channels.' . $this->name . '.max_files');
        }

        if (!in_array($this->format, [self::FORMAT_LINE, self::FORMAT_JSON], true)) {
            throw InvalidConfigException::invalidChoice('channels.' . $this->name . '.format', $this->format, [
                self::FORMAT_LINE,
                self::FORMAT_JSON,
            ]);
        }
    }

    /**
     * @param array<string,mixed> $config
     */
    public static function fromArray(string $name, array $config): self
    {
        return new self(
            name: $name,
            handler: HandlerType::fromConfig($config['handler'] ?? HandlerType::File->value, 'channels.' . $name . '.handler'),
            path: self::stringValue($config, 'path', 'logs', $name),
            maxFiles: self::intOrFalseValue($config, 'max_files', 14, $name),
            levels: self::levels($config['levels'] ?? ['info', 'warning', 'error', 'critical'], $name),
            format: self::stringValue($config, 'format', self::FORMAT_LINE, $name),
            perRun: self::boolValue($config, 'per_run', false, $name),
            fileNames: self::fileNames($config['file_names'] ?? null, $name),
        );
    }

    public function withHandler(HandlerType $handler): self
    {
        return new self(
            name: $this->name,
            handler: $handler,
            path: $this->path,
            maxFiles: $this->maxFiles,
            levels: $this->levels,
            format: $this->format,
            perRun: $this->perRun,
            fileNames: $this->fileNames,
            runSuffix: $this->runSuffix,
        );
    }

    public function withPath(string $path): self
    {
        return new self(
            name: $this->name,
            handler: $this->handler,
            path: $path,
            maxFiles: $this->maxFiles,
            levels: $this->levels,
            format: $this->format,
            perRun: $this->perRun,
            fileNames: $this->fileNames,
            runSuffix: $this->runSuffix,
        );
    }

    public function withRunSuffix(?string $runSuffix): self
    {
        return new self(
            name: $this->name,
            handler: $this->handler,
            path: $this->path,
            maxFiles: $this->maxFiles,
            levels: $this->levels,
            format: $this->format,
            perRun: $this->perRun,
            fileNames: $this->fileNames,
            runSuffix: $runSuffix,
        );
    }

    /**
     * @return list<Level>
     */
    private static function levels(mixed $levels, string $channel): array
    {
        if (!is_array($levels) || array_is_list($levels) === false) {
            throw InvalidConfigException::invalidList('channels.' . $channel . '.levels');
        }

        return array_map(static function (mixed $level) use ($channel): Level {
            if ($level instanceof Level) {
                return $level;
            }

            if (is_string($level)) {
                return match (strtolower($level)) {
                    'debug' => Level::Debug,
                    'info' => Level::Info,
                    'notice' => Level::Notice,
                    'warning' => Level::Warning,
                    'error' => Level::Error,
                    'critical' => Level::Critical,
                    'alert' => Level::Alert,
                    'emergency' => Level::Emergency,
                    default => throw InvalidConfigException::invalidChoice(
                        'channels.' . $channel . '.levels',
                        $level,
                        self::allowedLevels(),
                    ),
                };
            }

            if (is_int($level)) {
                return Level::from($level);
            }

            throw InvalidConfigException::invalidChoice(
                'channels.' . $channel . '.levels',
                get_debug_type($level),
                self::allowedLevels(),
            );
        }, $levels);
    }

    /**
     * @return list<string>
     */
    private static function allowedLevels(): array
    {
        return [
            'debug',
            'info',
            'notice',
            'warning',
            'error',
            'critical',
            'alert',
            'emergency',
        ];
    }

    /**
     * @return array<string,string>
     */
    private static function fileNames(mixed $fileNames, string $channel): array
    {
        if ($fileNames === null) {
            return [
                'debug' => 'debug.log',
                'info' => 'info.log',
                'notice' => 'info.log',
                'warning' => 'error.log',
                'error' => 'error.log',
                'critical' => 'error.log',
                'alert' => 'error.log',
                'emergency' => 'error.log',
            ];
        }

        if (!is_array($fileNames)) {
            throw InvalidConfigException::invalidList('channels.' . $channel . '.file_names');
        }

        $names = [];

        foreach ($fileNames as $level => $fileName) {
            if (!is_string($level) || !is_string($fileName) || $fileName === '') {
                throw InvalidConfigException::invalidList('channels.' . $channel . '.file_names');
            }

            $names[$level] = $fileName;
        }

        return $names;
    }

    /**
     * @param array<string,mixed> $config
     */
    private static function boolValue(array $config, string $key, bool $default, string $channel): bool
    {
        if (!array_key_exists($key, $config)) {
            return $default;
        }

        if (!is_bool($config[$key])) {
            throw InvalidConfigException::invalidBoolean('channels.' . $channel . '.' . $key);
        }

        return $config[$key];
    }

    /**
     * @param array<string,mixed> $config
     */
    private static function stringValue(array $config, string $key, string $default, string $channel): string
    {
        if (!array_key_exists($key, $config)) {
            return $default;
        }

        if (!is_string($config[$key]) || $config[$key] === '') {
            throw InvalidConfigException::missingString('channels.' . $channel . '.' . $key);
        }

        return $config[$key];
    }

    /**
     * @param array<string,mixed> $config
     */
    private static function intOrFalseValue(array $config, string $key, int $default, string $channel): int|false
    {
        if (!array_key_exists($key, $config)) {
            return $default;
        }

        if ($config[$key] === false) {
            return false;
        }

        if (!is_int($config[$key])) {
            throw InvalidConfigException::invalidPositiveIntegerOrFalse('channels.' . $channel . '.' . $key);
        }

        return $config[$key];
    }
}
