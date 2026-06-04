<?php

declare(strict_types=1);

namespace Edrard\Elog\Support;

use function array_slice;
use function glob;
use function is_file;
use function rsort;
use function unlink;

final readonly class LogFileCleaner
{
    public function __construct(
        private LogFilePathResolver $resolver = new LogFilePathResolver(),
    ) {
    }

    public function clean(string $directory, string $fileName, int|false $maxFiles): void
    {
        if ($maxFiles === false) {
            return;
        }

        $files = glob($this->resolver->pattern($directory, $fileName));

        if ($files === false || count($files) <= $maxFiles) {
            return;
        }

        rsort($files, SORT_STRING);

        foreach (array_slice($files, $maxFiles) as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}
