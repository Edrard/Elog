<?php

declare(strict_types=1);

namespace Edrard\Elog\Processor;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

final readonly class RequestIdProcessor implements ProcessorInterface
{
    /**
     * @param array<string,string> $server
     */
    public function __construct(
        private array $server = [],
        private string $extraKey = 'request_id',
    ) {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $requestId = $this->server['HTTP_X_REQUEST_ID']
            ?? $this->server['HTTP_X_CORRELATION_ID']
            ?? $this->server['UNIQUE_ID']
            ?? null;

        if ($requestId !== null && $requestId !== '') {
            $record->extra[$this->extraKey] = $requestId;
        }

        return $record;
    }
}
