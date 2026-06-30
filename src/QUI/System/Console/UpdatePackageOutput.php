<?php

/**
 * This file contains QUI\System\Console\UpdatePackageOutput
 */

namespace QUI\System\Console;

use QUI\Interfaces\System\SystemOutput;

use function preg_replace;
use function str_contains;
use function str_starts_with;
use function trim;

/**
 * Formats legacy package update output for the modern update command output.
 */
class UpdatePackageOutput implements SystemOutput
{
    private bool $requirementsHeadlineWritten = false;

    public function __construct(
        private UpdateConsoleOutput $Output,
        private int $verbosity = 0
    ) {
    }

    public function writeLn(string $msg = '', bool|string $color = false, bool|string $bg = false): void
    {
        $message = trim($msg);

        if ($message === '') {
            return;
        }

        if ($message === 'Cleanup database') {
            $this->Output->info('Cleanup database');
            return;
        }

        if (str_contains($message, 'Your quiqqer system is missing some requirements')) {
            if (!$this->requirementsHeadlineWritten) {
                $this->Output->warning('Your quiqqer system is missing some requirements');
                $this->requirementsHeadlineWritten = true;
            }

            return;
        }

        if (str_starts_with($message, '#')) {
            return;
        }

        if (str_contains($message, 'Cron did not run')) {
            $message = (string)preg_replace('/^[^A-Za-z0-9-]+/', '', $message);
            $this->Output->warning($message);
            return;
        }

        if (str_starts_with($message, '- ')) {
            $message = trim($message, " \t\n\r\0\x0B-");
        }

        if (str_contains($message, 'Aktualisierung wurde')) {
            $this->Output->success($message);
            return;
        }

        if ($this->verbosity > 0) {
            $this->Output->info($message);
        }
    }

    public function write(string $msg, bool|string $color = false, bool|string $bg = false): void
    {
        $this->writeLn($msg, $color, $bg);
    }
}
