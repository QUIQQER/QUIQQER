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
     * @param string|bool $color
     * @param string|bool $bg
     * @return void
     */
    public function writeLn(string $msg = '', $color = false, $bg = false);

    /**
     * Writes an output
     *
     * @param string $msg
     * @param string|bool $color
     * @param string|bool $bg
     * @return void
     */
    public function write(string $msg, $color = false, $bg = false);
}
