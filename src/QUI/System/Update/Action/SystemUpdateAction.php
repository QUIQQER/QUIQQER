<?php

namespace QUI\System\Update\Action;

use QUI\System\Console\Tools\Update;
use QUI\System\Update\RunActionInterface;
use QUI\System\Update\RunActionResult;
use QUI\System\Update\RunState;
use RuntimeException;

use const PHP_EOL;

class SystemUpdateAction implements RunActionInterface
{
    public function execute(RunState $state): RunActionResult
    {
        $Update = new Update();
        $Update->setUpdateOutputSectionOffset(2);
        $Output = $this->createCliOutput();
        $Update->setAttribute('parent', $Output);
        $arguments = $state->getMetadata()['arguments'] ?? [];

        if (is_array($arguments)) {
            foreach ($arguments as $name => $value) {
                if ($name === 'use-runner') {
                    continue;
                }

                if (is_bool($value) || is_string($value)) {
                    $Update->setArgument((string)$name, $value);
                }
            }
        }

        if (!$Update->executeSystemUpdate()) {
            $message = $Output->getLastErrorMessage() ?: 'Update was aborted.';

            throw new RuntimeException($message);
        }

        return RunActionResult::next(RunState::PHASE_CLEANUP);
    }

    private function createCliOutput(): object
    {
        return new class {
            private string $lastErrorMessage = '';

            public function writeLn(string $msg = '', bool|string $color = false, bool|string $bg = false): void
            {
                $this->write($msg, $color, $bg);
                echo PHP_EOL;
                $this->rememberErrorMessage($msg, $color, $bg);
            }

            public function write(string $msg, bool|string $color = false, bool|string $bg = false): void
            {
                $prefix = $this->getAnsiCode($color, false) . $this->getAnsiCode($bg, true);

                if ($prefix === '') {
                    echo $msg;
                    return;
                }

                echo $prefix . $msg . "\033[0m";
            }

            public function message(string $msg, bool|string $color = false, bool|string $bg = false): void
            {
                $this->write($msg, $color, $bg);
            }

            public function clearMsg(): void
            {
                echo "\033[0m";
            }

            public function getLastErrorMessage(): string
            {
                return $this->lastErrorMessage;
            }

            public function readInput(): string
            {
                $input = defined('STDIN') ? fgets(STDIN) : file_get_contents('php://stdin');

                if ($input === false) {
                    return '';
                }

                return trim($input);
            }

            private function getAnsiCode(bool|string $color, bool $background): string
            {
                if (!is_string($color) || $color === '') {
                    return '';
                }

                $colors = [
                    'black' => 0,
                    'red' => 1,
                    'green' => 2,
                    'yellow' => 3,
                    'brown' => 3,
                    'blue' => 4,
                    'purple' => 5,
                    'cyan' => 6,
                    'white' => 7,
                    'light_green' => 2
                ];

                if (!isset($colors[$color])) {
                    return '';
                }

                return "\033[" . (($background ? 40 : 30) + $colors[$color]) . "m";
            }

            private function rememberErrorMessage(string $message, bool|string $color, bool|string $bg): void
            {
                $message = trim((string)preg_replace('/\033\[[0-9;]*m/', '', $message));

                if ($message === '') {
                    return;
                }

                if ($color === 'red' || $bg === 'red' || str_contains($message, '[error]')) {
                    $this->lastErrorMessage = $message;
                }
            }
        };
    }
}
