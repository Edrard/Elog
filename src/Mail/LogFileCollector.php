<?php

declare(strict_types=1);

namespace Edrard\Elog\Mail;

use DateTimeImmutable;
use DateTimeInterface;
use Edrard\Elog\Config\ChannelConfig;
use Edrard\Elog\Support\LogFilePathResolver;

use function file_exists;

use Monolog\Level;

use function strtolower;

class LogFileCollector
{
    public function __construct(
        private readonly LogFilePathResolver $resolver = new LogFilePathResolver(),
    ) {
    }

    /**
     * @return list<LogFile>
     */
    public function collect(ChannelConfig $channel, ?DateTimeInterface $date = null): array
    {
        $date ??= new DateTimeImmutable();
        $levelsByFile = $this->levelsByFile($channel);
        $files = [];

        foreach ($levelsByFile as $fileName => $levels) {
            $path = $this->resolver->path(
                $channel->path,
                $fileName,
                $channel->perRun,
                $channel->runSuffix,
                $date,
            );

            if ($path === null || !file_exists($path)) {
                continue;
            }

            $files[] = new LogFile(
                path: $path,
                label: implode(' ', $levels),
                levels: $levels,
            );
        }

        return $files;
    }

    /**
     * @return array<string,list<string>>
     */
    private function levelsByFile(ChannelConfig $channel): array
    {
        $levelsByFile = [];

        foreach ($channel->levels as $level) {
            $levelName = $this->levelName($level);
            $fileName = $channel->fileNames[$levelName] ?? $levelName . '.log';
            $levelsByFile[$fileName][] = $levelName;
        }

        return $levelsByFile;
    }

    private function levelName(Level $level): string
    {
        return strtolower($level->getName());
    }
}
