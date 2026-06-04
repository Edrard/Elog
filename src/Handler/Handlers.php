<?php

declare(strict_types=1);

namespace Edrard\Elog\Handler;

use Monolog\Handler\HandlerInterface;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Level;

final class Handlers
{
    /**
     * @return list<HandlerInterface>
     */
    public static function stdout(Level $level = Level::Debug, bool $bubble = true): array
    {
        return [
            new StreamHandler('php://stdout', $level, $bubble),
        ];
    }

    /**
     * @return list<HandlerInterface>
     */
    public static function stderr(Level $level = Level::Debug, bool $bubble = true): array
    {
        return [
            new StreamHandler('php://stderr', $level, $bubble),
        ];
    }

    /**
     * @return list<HandlerInterface>
     */
    public static function null(Level $level = Level::Debug): array
    {
        return [
            new NullHandler($level),
        ];
    }
}
