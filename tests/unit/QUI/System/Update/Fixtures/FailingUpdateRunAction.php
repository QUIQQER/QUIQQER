<?php

namespace QUI\System\Update\Fixtures;

use QUI\System\Update\RunActionInterface;
use QUI\System\Update\RunActionResult;
use QUI\System\Update\RunState;
use RuntimeException;

class FailingUpdateRunAction implements RunActionInterface
{
    public function execute(RunState $state): RunActionResult
    {
        throw new RuntimeException('action failed');
    }
}
