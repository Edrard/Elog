<?php

declare(strict_types=1);

namespace Edrard\Elog\Support;

use Edrard\Elog\Exception\InvalidConfigException;

final class PathResolver
{
    public static function fromProjectRoot(string $path, string $projectRoot): string
    {
        if ($path === '') {
            throw InvalidConfigException::missingString('path');
        }

        if ($projectRoot === '') {
            throw InvalidConfigException::missingString('project_root');
        }

        if (self::isAbsolute($path)) {
            return self::normalize($path);
        }

        return self::normalize(rtrim($projectRoot, '/\\') . DIRECTORY_SEPARATOR . ltrim($path, '/\\'));
    }

    public static function isAbsolute(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        return str_starts_with($path, '/')
            || str_starts_with($path, '\\')
            || preg_match('/^[a-zA-Z]:[\/\\\\]/', $path) === 1;
    }

    private static function normalize(string $path): string
    {
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }
}
