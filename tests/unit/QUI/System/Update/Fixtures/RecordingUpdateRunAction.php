<?php

namespace QUI\System\Update\Fixtures;

use QUI\System\Update\RunActionInterface;
use QUI\System\Update\RunActionResult;
use QUI\System\Update\RunState;

class RecordingUpdateRunAction implements RunActionInterface
{
    public array $executedRunIds = [];

    public array $metadata = [];

    public function __construct(private readonly RunActionResult $result)
    {
    }

    public function execute(RunState $state): RunActionResult
    {
        $this->executedRunIds[] = $state->getId();
        $this->metadata[] = $state->getMetadata();

        return $this->result;
    }
}
