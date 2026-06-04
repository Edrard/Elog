<?php

declare(strict_types=1);

namespace Edrard\Elog\Support;

use DateTimeImmutable;
use DateTimeInterface;

use function pathinfo;
use function rtrim;

final class LogFilePathResolver
{
    public function path(
        string $directory,
        string $fileName,
        bool $perRun,
        ?string $runSuffix = null,
        ?DateTimeInterface $date = null,
    ): ?string {
        $resolvedFileName = $perRun
            ? $this->perRunFileName($fileName, $runSuffix)
            : $this->dailyFileName($fileName, $date ?? new DateTimeImmutable());

        if ($resolvedFileName === null) {
            return null;
        }

        return rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . $resolvedFileName;
    }

    public function pattern(string $directory, string $fileName): string
    {
        $file = pathinfo($fileName);
        $baseName = $file['filename'];
        $extension = isset($file['extension']) ? '.' . $file['extension'] : '';

        return rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . $baseName . '-*' . $extension;
    }

    private function dailyFileName(string $fileName, DateTimeInterface $date): string
    {
        $file = pathinfo($fileName);
        $baseName = $file['filename'];
        $extension = isset($file['extension']) ? '.' . $file['extension'] : '';

        return $baseName . '-' . $date->format('Y-m-d') . $extension;
    }

    private function perRunFileName(string $fileName, ?string $runSuffix): ?string
    {
        if ($runSuffix === null) {
            return null;
        }

        $file = pathinfo($fileName);
        $baseName = $file['filename'];
        $extension = isset($file['extension']) ? '.' . $file['extension'] : '';

        return $baseName . '-' . $runSuffix . $extension;
    }
}
