<?php

declare(strict_types=1);

namespace Edrard\Elog\Exception;

use RuntimeException;

class ElogException extends RuntimeException
{
    public static function notBooted(): self
    {
        return new self('Elog is not booted. Call Elog::boot() before writing logs.');
    }
}
