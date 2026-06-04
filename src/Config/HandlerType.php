<?php

declare(strict_types=1);

namespace Edrard\Elog\Config;

use Edrard\Elog\Exception\InvalidConfigException;

enum HandlerType: string
{
    case File = 'file';
    case Stdout = 'stdout';
    case Stderr = 'stderr';
    case Null = 'null';

    public static function fromConfig(mixed $value, string $key = 'handler'): self
    {
        if (!is_string($value) || $value === '') {
            throw InvalidConfigException::missingString($key);
        }

        return self::tryFrom($value)
            ?? throw InvalidConfigException::invalidChoice($key, $value, self::values());
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $type): string => $type->value,
            self::cases(),
        );
    }
}
