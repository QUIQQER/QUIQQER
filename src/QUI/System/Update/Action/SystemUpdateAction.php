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
        $Update->setAttribute('parent', $this->createCliOutput());
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
            throw new RuntimeException('Update was aborted.');
        }

        return RunActionResult::next(RunState::PHASE_CLEANUP);
    }

    private function createCliOutput(): object
    {
        return new class {
            public function writeLn(string $msg = '', bool|string $color = false, bool|string $bg = false): void
            {
                $this->write($msg, $color, $bg);
                echo PHP_EOL;
            }

            public function write(string $msg, bool|string $color = false, bool|string $bg = false): void
            {
                echo $msg;
            }

            public function clearMsg(): void
            {
            }
        };
    }
}
