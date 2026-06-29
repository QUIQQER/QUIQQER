<?php

namespace QUI\System\Update\Action;

use QUI\System\Console\Tools\Update;
use QUI\System\Update\RunActionInterface;
use QUI\System\Update\RunActionResult;
use QUI\System\Update\RunState;

class SystemUpdateAction implements RunActionInterface
{
    public function execute(RunState $state): RunActionResult
    {
        $Update = new Update();
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

        $Update->executeSystemUpdate();

        return RunActionResult::next(RunState::PHASE_CLEANUP);
    }
}
