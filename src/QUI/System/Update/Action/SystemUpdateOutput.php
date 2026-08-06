<?php

namespace QUI\System\Update\Action;

use QUI\Interfaces\System\SystemOutput;

interface SystemUpdateOutput extends SystemOutput
{
    public function getLastErrorMessage(): string;
}
