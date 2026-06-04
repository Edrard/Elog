<?php

declare(strict_types=1);

namespace Edrard\Elog\Mail;

final readonly class LogFile
{
    /**
     * @param list<string> $levels
     */
    public function __construct(
        public string $path,
        public string $label,
        public array $levels,
    ) {
    }

    /**
     * @param list<string> $importantLevels
     */
    public function isImportant(array $importantLevels): bool
    {
        foreach ($this->levels as $level) {
            if (in_array($level, $importantLevels, true)) {
                return true;
            }
        }

        return false;
    }
}
