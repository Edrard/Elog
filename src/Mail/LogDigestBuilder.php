<?php

declare(strict_types=1);

namespace Edrard\Elog\Mail;

use Edrard\Elog\Config\MailConfig;

use function file_get_contents;
use function sprintf;
use function strlen;
use function substr;

final class LogDigestBuilder
{
    /**
     * @param list<LogFile> $files
     *
     * @return list<LogDigest>
     */
    public function build(MailConfig $config, string $channel, array $files): array
    {
        $files = $this->filterFiles($config, $files);

        if ($files === []) {
            return [];
        }

        if ($config->separate) {
            return array_map(
                fn (LogFile $file): LogDigest => $this->singleDigest($config, $channel, $file),
                $files,
            );
        }

        return [
            $this->combinedDigest($config, $channel, $files),
        ];
    }

    /**
     * @param list<LogFile> $files
     *
     * @return list<LogFile>
     */
    private function filterFiles(MailConfig $config, array $files): array
    {
        if (!$config->onlyImportant) {
            return $files;
        }

        return array_values(array_filter(
            $files,
            static fn (LogFile $file): bool => $file->isImportant($config->importantLevels),
        ));
    }

    private function singleDigest(MailConfig $config, string $channel, LogFile $file): LogDigest
    {
        $body = $this->body($config, [$file]);

        return new LogDigest(
            subject: sprintf('[%s %s %s]', $channel, $file->label, $config->subject),
            body: $body,
            attachments: $config->attachLogs ? [$file->path] : [],
        );
    }

    /**
     * @param list<LogFile> $files
     */
    private function combinedDigest(MailConfig $config, string $channel, array $files): LogDigest
    {
        return new LogDigest(
            subject: sprintf('[%s %s]', $channel, $config->subject),
            body: $this->body($config, $files),
            attachments: $config->attachLogs ? array_map(static fn (LogFile $file): string => $file->path, $files) : [],
        );
    }

    /**
     * @param list<LogFile> $files
     */
    private function body(MailConfig $config, array $files): string
    {
        if ($config->attachLogs) {
            return $this->truncate($this->summary($files), $config->maxBodySize);
        }

        $body = '';

        foreach ($files as $file) {
            $content = file_get_contents($file->path);

            if ($content === false) {
                continue;
            }

            $body .= "\n\n" . $file->label . "\n\n" . $content;
        }

        return $this->truncate($body, $config->maxBodySize);
    }

    /**
     * @param list<LogFile> $files
     */
    private function summary(array $files): string
    {
        $summary = "Log files are attached:\n";

        foreach ($files as $file) {
            $summary .= '- ' . $file->label . ': ' . $file->path . "\n";
        }

        return $summary;
    }

    private function truncate(string $body, int $maxBodySize): string
    {
        if (strlen($body) <= $maxBodySize) {
            return $body;
        }

        return substr($body, 0, $maxBodySize) . "\n\n[Log body truncated by Elog.]";
    }
}
