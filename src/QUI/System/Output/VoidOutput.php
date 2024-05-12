<?php

namespace QUI\System\Output;

use QUI\Interfaces\System\SystemOutput;

/**
 * Class to use a system output interface without an output
 */
class VoidOutput implements SystemOutput
{
    public function write(string $msg, bool|string $color = false, bool|string $bg = false): void
    {
    }

    public function writeLn(string $msg = '', bool|string $color = false, bool|string $bg = false): void
    {
    }
}
