<?php

namespace QUI\System\Update;

interface RunActionInterface
{
    public function execute(RunState $state): RunActionResult;
}
