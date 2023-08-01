<?php

namespace QUI\System;

use QUI\Interfaces\System\SystemOutput;

/**
 * Class to use a system output interface without an output
 */
class VoidOutput implements SystemOutput
{
    public function write(string $msg, $color = false, $bg = false)
    {
    }

    public function writeLn(string $msg = '', $color = false, $bg = false)
    {
    }
}
