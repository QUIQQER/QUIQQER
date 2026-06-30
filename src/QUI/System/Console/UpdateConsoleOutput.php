<?php

/**
 * This file contains QUI\System\Console\UpdateConsoleOutput
 */

namespace QUI\System\Console;

use QUI\Interfaces\System\SystemOutput;

use function max;
use function method_exists;
use function str_pad;
use function strlen;

use const PHP_EOL;

/**
 * Small CLI output helper for the update command.
 */
class UpdateConsoleOutput
{
    private int $section = 0;

    public function __construct(
        private SystemOutput $Output,
        private int $totalSections = 6
    ) {
    }

    public function section(string $title): void
    {
        if ($this->section > 0) {
            $this->Output->writeLn();
        }

        $this->section++;

        $this->Output->writeLn('[' . $this->section . '/' . $this->totalSections . '] ' . $title);
    }

    public function setCurrentSection(int $section): void
    {
        $this->section = $section;
    }

    public function info(string $message): void
    {
        $this->Output->writeLn('  [..] ' . $message);
    }

    public function success(string $message): void
    {
        $this->Output->writeLn('  [OK] ' . $message, 'green');
        $this->resetColor();
    }

    public function warning(string $message): void
    {
        $this->Output->writeLn();
        $this->Output->writeLn('  [!!] ' . $message, 'yellow');
        $this->resetColor();
    }

    public function question(string $message): void
    {
        $this->Output->write('  [?] ' . $message . ' ', 'cyan');
        $this->resetColor();
    }

    public function quote(string $message, bool|string $color = false): void
    {
        $this->Output->writeLn('  > ' . $message, $color);
        $this->resetColor();
    }

    /**
     * @param array<int, string> $lines
     */
    public function errorBox(array $lines): void
    {
        $width = 0;

        foreach ($lines as $line) {
            $width = max($width, strlen($line));
        }

        $border = str_pad('', $width + 4);

        $this->Output->writeLn();
        $this->Output->writeLn($border, 'white', 'red');

        foreach ($lines as $line) {
            $this->Output->writeLn('  ' . str_pad($line, $width) . '  ', 'white', 'red');
        }

        $this->Output->writeLn($border, 'white', 'red');
        $this->resetColor();
        $this->Output->writeLn();
        $this->Output->writeLn();
    }

    private function resetColor(): void
    {
        if (method_exists($this->Output, 'resetColor')) {
            $this->Output->resetColor();
        }
    }
}
