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
    /**
     * Writes a new line
     *
     * @param string $msg
     * @param bool|string $color
     * @param bool|string $bg
     * @return void
     */
    public function writeLn(string $msg = '', bool|string $color = false, bool|string $bg = false): void;

    /**
     * Writes an output
     *
     * @param string $msg
     * @param bool|string $color
     * @param bool|string $bg
     * @return void
     */
    public function write(string $msg, bool|string $color = false, bool|string $bg = false): void;
}
