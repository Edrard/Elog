<?php

declare(strict_types=1);

namespace Edrard\Elog\Exception;

final class InvalidConfigException extends ElogException
{
    public static function missingString(string $key): self
    {
        return new self(sprintf('Configuration value "%s" must be a non-empty string.', $key));
    }

    public static function invalidPositiveInteger(string $key): self
    {
        return new self(sprintf('Configuration value "%s" must be a positive integer.', $key));
    }

    public static function invalidPositiveIntegerOrFalse(string $key): self
    {
        return new self(sprintf('Configuration value "%s" must be a positive integer or false.', $key));
    }

    public static function invalidBoolean(string $key): self
    {
        return new self(sprintf('Configuration value "%s" must be a boolean.', $key));
    }

    public static function unreadableFile(string $path): self
    {
        return new self(sprintf('Configuration file "%s" is not readable.', $path));
    }

    public static function invalidJson(string $path, string $message): self
    {
        return new self(sprintf('Configuration file "%s" contains invalid JSON: %s.', $path, $message));
    }

    public static function invalidObject(string $key): self
    {
        return new self(sprintf('Configuration value "%s" must be an object.', $key));
    }

    public static function invalidList(string $key): self
    {
        return new self(sprintf('Configuration value "%s" must be a list.', $key));
    }

    /**
     * @param list<string> $allowed
     */
    public static function invalidChoice(string $key, string $value, array $allowed): self
    {
        return new self(sprintf(
            'Configuration value "%s" has invalid value "%s". Allowed values: %s.',
            $key,
            $value,
            implode(', ', $allowed),
        ));
    }

    /**
     * @param list<string> $knownChannels
     */
    public static function unknownChannel(string $channel, array $knownChannels): self
    {
        return new self(sprintf(
            'Unknown log channel "%s". Known channels: %s.',
            $channel,
            $knownChannels === [] ? 'none' : implode(', ', $knownChannels),
        ));
    }
}
