<?php

declare(strict_types=1);

namespace Edrard\Elog\Tests\Processor;

use Edrard\Elog\Processor\RequestIdProcessor;
use Monolog\JsonSerializableDateTimeImmutable;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;

final class RequestIdProcessorTest extends TestCase
{
    public function testItAddsRequestIdWhenHeaderExists(): void
    {
        $record = new LogRecord(
            datetime: new JsonSerializableDateTimeImmutable(true),
            channel: 'app',
            level: Level::Info,
            message: 'test',
        );

        $processed = (new RequestIdProcessor(['HTTP_X_REQUEST_ID' => 'request-42']))($record);

        self::assertSame('request-42', $processed->extra['request_id']);
    }

    public function testItKeepsRecordUntouchedWhenRequestIdDoesNotExist(): void
    {
        $record = new LogRecord(
            datetime: new JsonSerializableDateTimeImmutable(true),
            channel: 'app',
            level: Level::Info,
            message: 'test',
        );

        $processed = (new RequestIdProcessor())($record);

        self::assertSame([], $processed->extra);
    }
}
