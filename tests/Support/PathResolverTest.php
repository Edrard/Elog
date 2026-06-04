<?php

declare(strict_types=1);

namespace Edrard\Elog\Tests\Support;

use Edrard\Elog\Support\PathResolver;
use PHPUnit\Framework\TestCase;

final class PathResolverTest extends TestCase
{
    public function testItResolvesRelativePathFromProjectRoot(): void
    {
        self::assertSame(
            implode(DIRECTORY_SEPARATOR, ['D:', 'Work', 'Project', 'logs']),
            PathResolver::fromProjectRoot('logs', 'D:/Work/Project'),
        );
    }

    public function testItKeepsAbsoluteWindowsPath(): void
    {
        self::assertSame(
            implode(DIRECTORY_SEPARATOR, ['D:', 'Logs']),
            PathResolver::fromProjectRoot('D:/Logs', 'D:/Work/Project'),
        );
    }
}
