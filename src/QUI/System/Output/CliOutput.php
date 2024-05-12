<?php

namespace QUI\System\Output;

use QUI\Interfaces\System\SystemOutput;

/**
 * Class to use a system output interface without an output
 */
class CliOutput implements SystemOutput
{
    /**
     * All available text colors
     */
    protected array $colors = [
        'black' => '0;30',
        'dark_gray' => '1;30',
        'blue' => '0;34',
        'light_blue' => '1;34',
        'green' => '0;32',
        'light_green' => '1;32',
        'cyan' => '0;36',
        'light_cyan' => '1;36',
        'red' => '1;31',
        'light_red' => '2;31',
        'purple' => '0;35',
        'light_purple' => '1;35',
        'brown' => '0;33',
        'yellow' => '1;33',
        'light_gray' => '0;37',
        'white' => '1;37',
        'black_u' => '4;30',
        'red_u' => '4;31',
        'green_u' => '4;32',
        'yellow_u' => '4;33',
        'blue_u' => '4;34',
        'purple_u' => '4;35',
        'cyan_u' => '4;36',
        'white_u' => '4;37'
    ];

    /**
     * All available background colors
     */
    protected array $bg = [
        'black' => '40',
        'red' => '41',
        'green' => '42',
        'yellow' => '43',
        'blue' => '44',
        'magenta' => '45',
        'cyan' => '46',
        'light_gray' => '47'
    ];

    public function write(string $msg, bool|string $color = false, bool|string $bg = false): void
    {
        echo "\033[0m";

        if (isset($this->colors[$color])) {
            echo "\033[" . $this->colors[$color] . "m";
        }

        if (isset($this->bg[$bg])) {
            echo "\033[" . $this->bg[$bg] . "m";
        }

        echo $msg;
    }

    public function writeLn(string $msg = '', bool|string $color = false, bool|string $bg = false): void
    {
        $this->write($msg, $color, $bg);
        echo PHP_EOL;
    }
}
