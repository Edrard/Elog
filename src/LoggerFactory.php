<?php

declare(strict_types=1);

namespace Edrard\Elog;

use Edrard\Elog\Config\ChannelConfig;
use Edrard\Elog\Config\HandlerType;
use Edrard\Elog\Config\ProcessorConfig;
use Edrard\Elog\Handler\FileHandler as ElogFileHandler;
use Edrard\Elog\Processor\RequestIdProcessor;
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\JsonFormatter;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\FilterHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\ProcessIdProcessor;
use Monolog\Processor\ProcessorInterface;
use Monolog\Processor\UidProcessor;

final class LoggerFactory
{
    /**
     * @param array<string,string> $server
     */
    public function __construct(
        private readonly array $server = [],
    ) {
    }

    public function create(ChannelConfig $channel, ProcessorConfig $processors = new ProcessorConfig()): Logger
    {
        return new Logger(
            $channel->name,
            $this->handlers($channel),
            $this->processors($processors),
        );
    }

    /**
     * @return list<HandlerInterface>
     */
    private function handlers(ChannelConfig $channel): array
    {
        return match ($channel->handler) {
            HandlerType::File => $this->fileHandlers($channel),
            HandlerType::Stdout => [
                $this->filtered(
                    new StreamHandler('php://stdout', Level::Debug),
                    $channel->levels,
                    $channel->format,
                ),
            ],
            HandlerType::Stderr => [
                $this->filtered(
                    new StreamHandler('php://stderr', Level::Debug),
                    $channel->levels,
                    $channel->format,
                ),
            ],
            HandlerType::Null => [
                new FilterHandler(new NullHandler(Level::Debug), $channel->levels, bubble: false),
            ],
        };
    }

    /**
     * @return list<HandlerInterface>
     */
    private function fileHandlers(ChannelConfig $channel): array
    {
        $levelsByFile = [];

        foreach ($channel->levels as $level) {
            $levelsByFile[$this->fileNameForLevel($channel, $level)][] = $level;
        }

        $handlers = [];
        $runSuffix = $channel->perRun ? ($channel->runSuffix ?? $this->runSuffix()) : null;

        foreach ($levelsByFile as $fileName => $levels) {
            $handler = new ElogFileHandler(
                directory: $channel->path,
                fileName: $fileName,
                perRun: $channel->perRun,
                runSuffix: $runSuffix,
                maxFiles: $channel->maxFiles,
            );

            $handlers[] = $this->filtered($handler, $levels, $channel->format);
        }

        return $handlers;
    }

    private function fileNameForLevel(ChannelConfig $channel, Level $level): string
    {
        $key = strtolower($level->getName());

        return $channel->fileNames[$key] ?? $key . '.log';
    }

    private function runSuffix(): string
    {
        return date('Y-m-d_H-i-s') . '_' . bin2hex(random_bytes(3));
    }

    /**
     * @param list<Level> $levels
     */
    private function filtered(HandlerInterface $handler, array $levels, string $format): HandlerInterface
    {
        if ($handler instanceof \Monolog\Handler\FormattableHandlerInterface) {
            $handler->setFormatter($this->formatter($format));
        }

        return new FilterHandler($handler, $levels, bubble: false);
    }

    private function formatter(string $format): FormatterInterface
    {
        return match ($format) {
            ChannelConfig::FORMAT_JSON => new JsonFormatter(
                JsonFormatter::BATCH_MODE_JSON,
                appendNewline: true,
                ignoreEmptyContextAndExtra: true,
            ),
            default => new LineFormatter(ignoreEmptyContextAndExtra: true),
        };
    }

    /**
     * @return list<ProcessorInterface>
     */
    private function processors(ProcessorConfig $config): array
    {
        if (!$config->enabled) {
            return [];
        }

        $processors = [];

        if ($config->memoryUsage) {
            $processors[] = new MemoryUsageProcessor();
        }

        if ($config->processId) {
            $processors[] = new ProcessIdProcessor();
        }

        if ($config->uid) {
            $processors[] = new UidProcessor();
        }

        if ($config->requestId) {
            $processors[] = new RequestIdProcessor($this->server);
        }

        return $processors;
    }
}
