<?php

namespace QUI\System\Update\Action;

use QUI\System\Update\RunActionInterface;
use QUI\System\Update\RunActionResult;
use QUI\System\Update\RunState;

class PhaseTransitionAction implements RunActionInterface
{
    public function __construct(private readonly string $nextPhase)
    {
    }

    public function execute(RunState $state): RunActionResult
    {
        return RunActionResult::next($this->nextPhase);
    }
}
