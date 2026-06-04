<?php

declare(strict_types=1);

namespace Edrard\Elog\Tests;

use Edrard\Elog\Exception\ElogException;
use Edrard\Elog\Timer;
use PHPUnit\Framework\TestCase;

final class TimerTest extends TestCase
{
    protected function tearDown(): void
    {
        Timer::reset();
    }

    public function testItMeasuresElapsedTimeWithoutStoppingTimer(): void
    {
        Timer::start('import');
        usleep(1000);

        $first = Timer::elapsed('import', 6);
        usleep(1000);
        $second = Timer::elapsed('import', 6);

        self::assertTrue(Timer::isRunning('import'));
        self::assertGreaterThan(0.0, $first);
        self::assertGreaterThan($first, $second);
    }

    public function testItCanStopTimerAndKeepStableElapsedTime(): void
    {
        Timer::start('export');
        usleep(1000);

        $stopped = Timer::stop('export', 6);
        usleep(1000);

        self::assertFalse(Timer::isRunning('export'));
        self::assertSame($stopped, Timer::elapsed('export', 6));
    }

    public function testItSupportsMultipleIndependentTimers(): void
    {
        Timer::start('first');
        usleep(1000);
        Timer::start('second');
        usleep(1000);

        self::assertGreaterThan(Timer::elapsed('second', 6), Timer::elapsed('first', 6));
    }

    public function testItCanResetSingleTimer(): void
    {
        Timer::start('first');
        Timer::start('second');

        Timer::reset('first');

        self::assertFalse(Timer::has('first'));
        self::assertTrue(Timer::has('second'));
    }

    public function testItKeepsLegacyMethodNames(): void
    {
        Timer::startTime('legacy');
        usleep(1000);

        $elapsed = Timer::getTime('legacy', 6);
        $stopped = Timer::endTime('legacy', 6);

        self::assertGreaterThan(0.0, $elapsed);
        self::assertGreaterThanOrEqual($elapsed, $stopped);
    }

    public function testItRejectsReadingTimerThatWasNotStarted(): void
    {
        $this->expectException(ElogException::class);

        Timer::elapsed('missing');
    }

    public function testItRejectsEmptyTimerName(): void
    {
        $this->expectException(ElogException::class);

        Timer::start('');
    }
}
