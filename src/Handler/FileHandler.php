<?php

declare(strict_types=1);

namespace Edrard\Elog\Handler;

use function dirname;

use Edrard\Elog\Support\LogFileCleaner;
use Edrard\Elog\Support\LogFilePathResolver;

use function error_get_last;
use function fclose;
use function flock;
use function fopen;
use function fwrite;
use function is_dir;
use function is_resource;
use function is_string;
use function mkdir;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;

use function sprintf;
use function strlen;
use function substr;

use UnexpectedValueException;

final class FileHandler extends AbstractProcessingHandler
{
    /**
     * @var array<string,true>
     */
    private array $cleanedPaths = [];

    /**
     * @var resource|null
     */
    private $stream = null;
    private ?string $streamPath = null;

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

    public function close(): void
    {
        $this->closeStream();
        parent::close();
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

        if ($this->streamPath !== $path) {
            $this->closeStream();
            $this->stream = $this->openStream($path);
            $this->streamPath = $path;
        }

        if (!is_string($record->formatted)) {
            throw new UnexpectedValueException('The file log formatter must return a string.');
        }

        $stream = $this->stream;

        if (!is_resource($stream)) {
            throw new UnexpectedValueException(sprintf('The log file "%s" is not open.', $path));
        }

        $this->writeToStream($stream, $record->formatted, $path);

        if (!isset($this->cleanedPaths[$path])) {
            $this->cleaner->clean($this->directory, $this->fileName, $this->maxFiles);
            $this->cleanedPaths[$path] = true;
        }
    }

    /**
     * @return resource
     */
    private function openStream(string $path)
    {
        $directory = dirname($path);

        if (!is_dir($directory) && !@mkdir($directory, recursive: true) && !is_dir($directory)) {
            $error = error_get_last();

            throw new UnexpectedValueException(sprintf('The log directory "%s" could not be created: ' . ($error['message'] ?? 'Unknown error'), $directory));
        }

        $stream = @fopen($path, 'ab');

        if (!is_resource($stream)) {
            $error = error_get_last();

            throw new UnexpectedValueException(sprintf('The log file "%s" could not be opened: ' . ($error['message'] ?? 'Unknown error'), $path));
        }

        return $stream;
    }

    /**
     * @param resource $stream
     */
    private function writeToStream($stream, string $content, string $path): void
    {
        if (!flock($stream, LOCK_EX)) {
            throw new UnexpectedValueException(sprintf('The log file "%s" could not be locked.', $path));
        }

        try {
            $offset = 0;
            $length = strlen($content);

            while ($offset < $length) {
                $written = fwrite($stream, substr($content, $offset));

                if ($written === false || $written === 0) {
                    throw new UnexpectedValueException(sprintf('The log file "%s" could not be written.', $path));
                }

                $offset += $written;
            }
        } finally {
            flock($stream, LOCK_UN);
        }
    }

    private function closeStream(): void
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }

        $this->stream = null;
        $this->streamPath = null;
    }
}
