<?php

declare(strict_types=1);

namespace Edrard\Elog\Mail;

use Edrard\Elog\Config\MailConfig;

use function file_get_contents;
use function filesize;
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
        $truncated = false;

        foreach ($files as $file) {
            $header = "\n\n" . $file->label . "\n\n";
            $remaining = $config->maxBodySize - strlen($body);

            if ($remaining <= 0) {
                $truncated = true;

                break;
            }

            if (strlen($header) >= $remaining) {
                $body .= substr($header, 0, $remaining);
                $truncated = true;

                break;
            }

            $body .= $header;
            $remaining -= strlen($header);

            if ($remaining < 1) {
                $truncated = true;

                break;
            }

            $fileSize = filesize($file->path);
            $content = file_get_contents($file->path, false, null, 0, $remaining);

            if ($content === false) {
                continue;
            }

            $body .= $content;

            if ($fileSize !== false ? $fileSize > $remaining : strlen($content) >= $remaining) {
                $truncated = true;

                break;
            }
        }

        return $truncated ? $body . "\n\n[Log body truncated by Elog.]" : $body;
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
