<?php

declare(strict_types=1);

namespace Edrard\Elog\Handler;

use function dirname;

use Edrard\Elog\Support\LogFileCleaner;
use Edrard\Elog\Support\LogFilePathResolver;

use function file_put_contents;
use function is_dir;
use function is_string;
use function mkdir;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;

use function sprintf;

use UnexpectedValueException;

final class FileHandler extends AbstractProcessingHandler
{
    /**
     * @var array<string,true>
     */
    private array $cleanedPaths = [];

    public function __construct(
        private readonly string $directory,
        private readonly string $fileName,
        private readonly bool $perRun,
        private readonly ?string $runSuffix,
        private readonly int|false $maxFiles,
        int|string|Level $level = Level::Debug,
        bool $bubble = true,
        private readonly LogFilePathResolver $resolver = new LogFilePathResolver(),
        private readonly LogFileCleaner $cleaner = new LogFileCleaner(),
    ) {
        parent::__construct($level, $bubble);
    }

    protected function write(LogRecord $record): void
    {
        $path = $this->resolver->path(
            $this->directory,
            $this->fileName,
            $this->perRun,
            $this->runSuffix,
            $record->datetime,
        );

        if ($path === null) {
            throw new UnexpectedValueException('Per-run log file cannot be resolved without a run suffix.');
        }

        $directory = dirname($path);

        if (!is_dir($directory)) {
            mkdir($directory, recursive: true);
        }

        if (!is_string($record->formatted)) {
            throw new UnexpectedValueException('The file log formatter must return a string.');
        }

        $written = file_put_contents($path, $record->formatted, FILE_APPEND | LOCK_EX);

        if ($written === false) {
            throw new UnexpectedValueException(sprintf('The log file "%s" could not be written.', $path));
        }

        if (!isset($this->cleanedPaths[$path])) {
            $this->cleaner->clean($this->directory, $this->fileName, $this->maxFiles);
            $this->cleanedPaths[$path] = true;
        }
    }
}
