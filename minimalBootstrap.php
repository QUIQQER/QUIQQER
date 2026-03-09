<?php

/**
 * This file is part of QUIQQER.
 *
 * (c) Henning Leutz <leutz@pcsg.de>
 * Moritz Scholz <scholz@pcsg.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * This file contains the minimal bootstrap for quiqqer
 * it includes the minimal header file
 */

if (!defined('QUIQQER_SYSTEM')) {
    exit;
}

if (!defined('ETC_DIR')) {
    $etc_dir = dirname(__FILE__, 4) . '/etc/';

    if (!is_dir($etc_dir)) {
        require_once 'quiqqer.php';
        exit;
    }

    define('ETC_DIR', $etc_dir);
}

require_once dirname(__FILE__) . '/src/autoload.php';
require_once dirname(__FILE__) . '/src/minimalHeader.php';
