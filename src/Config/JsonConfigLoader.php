<?php

declare(strict_types=1);

namespace Edrard\Elog\Config;

use Edrard\Elog\Exception\InvalidConfigException;
use Edrard\Elog\Support\PathResolver;

use function file_get_contents;
use function is_array;
use function json_decode;

use const JSON_THROW_ON_ERROR;

use JsonException;

final class JsonConfigLoader
{
    public static function load(string $path, string $projectRoot): ElogConfig
    {
        $json = @file_get_contents($path);

        if ($json === false) {
            throw InvalidConfigException::unreadableFile($path);
        }

        try {
            $config = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw InvalidConfigException::invalidJson($path, $exception->getMessage());
        }

        if (!is_array($config) || array_is_list($config)) {
            throw InvalidConfigException::invalidObject('root');
        }

        return self::resolvePaths(ElogConfig::fromArray(self::stringKeyArray($config, 'root')), $projectRoot);
    }

    private static function resolvePaths(ElogConfig $config, string $projectRoot): ElogConfig
    {
        return $config->mapChannels(
            static function (ChannelConfig $channel) use ($projectRoot): ChannelConfig {
                if ($channel->handler !== HandlerType::File) {
                    return $channel;
                }

                return $channel->withPath(PathResolver::fromProjectRoot($channel->path, $projectRoot));
            },
        );
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
                throw InvalidConfigException::invalidObject($key);
            }

            $result[$itemKey] = $value;
        }

        return $result;
    }
}
