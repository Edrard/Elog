<?php

declare(strict_types=1);

namespace Edrard\Elog\Mail;

final readonly class LogDigest
{
    /**
     * @param list<string> $attachments
     */
    public function __construct(
        public string $subject,
        public string $body,
        public array $attachments = [],
    ) {
    }
}
