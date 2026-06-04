<?php

declare(strict_types=1);

namespace Edrard\Elog;

use Edrard\Elog\Exception\ElogException;

final class Timer
{
    private const DEFAULT_NAME = 'global';

    /**
     * @var array<string,float>
     */
    private static array $startedAt = [];

    /**
     * @var array<string,float>
     */
    private static array $stoppedAt = [];

    public static function start(string $name = self::DEFAULT_NAME): void
    {
        self::assertName($name);

        self::$startedAt[$name] = microtime(true);
        unset(self::$stoppedAt[$name]);
    }

    public static function restart(string $name = self::DEFAULT_NAME): void
    {
        self::start($name);
    }

    public static function stop(string $name = self::DEFAULT_NAME, ?int $precision = 2): float
    {
        self::assertStarted($name);

        self::$stoppedAt[$name] = microtime(true);

        return self::elapsed($name, $precision);
    }

    public static function elapsed(string $name = self::DEFAULT_NAME, ?int $precision = 2): float
    {
        self::assertStarted($name);

        $end = self::$stoppedAt[$name] ?? microtime(true);
        $elapsed = $end - self::$startedAt[$name];

        return self::round($elapsed, $precision);
    }

    public static function has(string $name = self::DEFAULT_NAME): bool
    {
        return isset(self::$startedAt[$name]);
    }

    public static function isRunning(string $name = self::DEFAULT_NAME): bool
    {
        return isset(self::$startedAt[$name]) && !isset(self::$stoppedAt[$name]);
    }

    public static function reset(?string $name = null): void
    {
        if ($name === null) {
            self::$startedAt = [];
            self::$stoppedAt = [];

            return;
        }

        self::assertName($name);

        unset(self::$startedAt[$name], self::$stoppedAt[$name]);
    }

    public static function startTime(string $type = self::DEFAULT_NAME): void
    {
        self::start($type);
    }

    public static function endTime(string $type = self::DEFAULT_NAME, ?int $round = 2): float
    {
        return self::stop($type, $round);
    }

    public static function getTime(string $type = self::DEFAULT_NAME, ?int $round = 2): float
    {
        return self::elapsed($type, $round);
    }

    private static function assertName(string $name): void
    {
        if ($name === '') {
            throw new ElogException('Timer name must be a non-empty string.');
        }
    }

    private static function assertStarted(string $name): void
    {
        self::assertName($name);

        if (!isset(self::$startedAt[$name])) {
            throw new ElogException(sprintf('Timer "%s" has not been started.', $name));
        }
    }

    private static function round(float $value, ?int $precision): float
    {
        if ($precision === null) {
            return $value;
        }

        if ($precision < 0) {
            throw new ElogException('Timer precision must be greater than or equal to zero.');
        }

        return round($value, $precision);
    }
}
