<?php

/**
 * This file contains \QUI\Interfaces\System\SystemOutput
 */

namespace QUI\Interfaces\System;

/**
 * Manage output for the different possibilities
 * eg:
 * - Web Output
 * - CLI Output
 */
interface SystemOutput
{
    public function writeLn(string $msg = '', bool|string $color = false, bool|string $bg = false): void;

    public function write(string $msg, bool|string $color = false, bool|string $bg = false): void;
}
